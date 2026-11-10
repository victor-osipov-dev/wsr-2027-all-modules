<?php

namespace App\Http\Controllers;

class AdminController extends \Illuminate\Routing\Controller
{
    private const SORTS = [
        'newest_desc' => ['created_at', 'desc'],
        'newest_asc' => ['created_at', 'asc'],
        'time_asc' => ['cooking_time', 'asc'],
        'time_desc' => ['cooking_time', 'desc'],
    ];
    private const COLUMNS = ['title', 'description', 'cooking_time', 'calories', 'difficulty', 'price', 'category_id'];
    public function login(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email'], 'password' => ['required']],
            [
                'email.required' => 'Введите e-mail.',
                'email.email' => 'Некорректный e-mail.',
                'password.required' => 'Введите пароль.',
            ],
        );
        if (
            !\Illuminate\Support\Facades\Auth::attempt(
                $request->only('email', 'password'),
                $request->boolean('remember'),
            ) ||
            \Illuminate\Support\Facades\Auth::user()->role !== 'admin'
        ) {
            \Illuminate\Support\Facades\Auth::logout();
            return back()->withInput($request->only('email'))->with('auth_error', 'Неправильный логин или пароль');
        }
        $request->session()->regenerate();
        return redirect()->route('admin.recipes.index')->with('success', 'Вы вошли как администратор.');
    }
    public function logout(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
    public function index(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        return view('recipes', $this->listData($request, null));
    }
    public function create(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        return view('recipes', $this->listData($request, new \App\Models\Recipe()));
    }
    public function store(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $this->validateRecipe($request, null);
        $recipe = \App\Models\Recipe::create(
            array_intersect_key($data, array_flip(self::COLUMNS)) + ['user_id' => $request->user()->id],
        );
        $this->syncIngredients($recipe, (array) $request->input('ingredients', []));
        $this->syncSteps($recipe, (array) $request->input('steps', []));
        $this->storeImages($recipe, (array) $request->file('images', []), $request->input('default_image'), []);
        return redirect()->route('admin.recipes.index')->with('success', 'Рецепт успешно создан.');
    }
    public function edit(\Illuminate\Http\Request $request, \App\Models\Recipe $recipe): \Illuminate\View\View
    {
        return view('recipes', $this->listData($request, $recipe->load(['images', 'ingredients', 'steps'])));
    }
    public function update(
        \Illuminate\Http\Request $request,
        \App\Models\Recipe $recipe,
    ): \Illuminate\Http\RedirectResponse {
        $titleChanged = $recipe->title !== (string) $request->input('title');
        $data = $this->validateRecipe($request, $recipe);
        $recipe->update(array_intersect_key($data, array_flip(self::COLUMNS)));
        $this->syncIngredients($recipe, (array) $request->input('ingredients', []));
        $this->syncSteps($recipe, (array) $request->input('steps', []));
        $this->storeImages(
            $recipe,
            (array) $request->file('images', []),
            $request->input('default_image'),
            (array) $request->input('delete_images', []),
        );
        if ($titleChanged) {
            $this->regenerateThumbs($recipe->load('images'));
        }
        return redirect()->route('admin.recipes.index')->with('success', 'Рецепт успешно обновлён.');
    }
    public function destroy(\App\Models\Recipe $recipe): \Illuminate\Http\RedirectResponse
    {
        $recipe->delete();
        return redirect()->route('admin.recipes.index')->with('success', 'Рецепт помечен на удаление.');
    }
    public function restore(\App\Models\Recipe $recipe): \Illuminate\Http\RedirectResponse
    {
        $recipe->restore();
        return redirect()->route('admin.recipes.index')->with('success', 'Рецепт восстановлен.');
    }
    public function forceDelete(\App\Models\Recipe $recipe): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('recipes/' . $recipe->getKey());
        $recipe->forceDelete();
        return redirect()->route('admin.recipes.index')->with('success', 'Рецепт удалён окончательно.');
    }
    private function listData(\Illuminate\Http\Request $request, ?\App\Models\Recipe $form): array
    {
        $filters = $request->only(['difficulty', 'time_range', 'category_id', 'price']);
        $sort = (string) $request->input('sort', 'newest_desc');
        [$column, $direction] = self::SORTS[$sort] ?? self::SORTS['newest_desc'];
        $recipes = \App\Models\Recipe::withTrashed()
            ->with(['category', 'images'])
            ->when($filters['difficulty'], fn($q, $v) => $q->where('difficulty', $v))
            ->when($filters['category_id'], fn($q, $v) => $q->where('category_id', $v))
            ->when($filters['time_range'] === 'up_to_15', fn($q) => $q->where('cooking_time', '<=', 15))
            ->when($filters['time_range'] === 'from_60_to_120', fn($q) => $q->whereBetween('cooking_time', [60, 120]))
            ->when($filters['time_range'] === 'over_120', fn($q) => $q->where('cooking_time', '>', 120))
            ->when(
                is_numeric($filters['price']) && (float) $filters['price'] > 0,
                fn($q) => $q->where('price', '<=', (float) $filters['price']),
            )
            ->orderBy($column, $direction)
            ->paginate(5)
            ->withQueryString();
        return [
            'recipes' => $recipes,
            'filters' => $filters,
            'sort' => $sort,
            'form' => $form,
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'ingredients' => \App\Models\Ingredient::orderBy('title')->get(),
            'units' => \App\Models\Ingredient::UNITS,
        ];
    }
    private function validateRecipe(\Illuminate\Http\Request $request, ?\App\Models\Recipe $recipe): array
    {
        $request->merge([
            'ingredients' => array_values(
                array_filter((array) $request->input('ingredients', []), fn($r) => filled($r['ingredient_id'] ?? null)),
            ),
            'steps' => array_values(
                array_filter(
                    (array) $request->input('steps', []),
                    fn($r) => filled($r['title'] ?? null) || filled($r['description'] ?? null),
                ),
            ),
            'price' => $request->input('price') === '' ? 0 : $request->input('price'),
        ]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'min:50', 'max:250'],
            'cooking_time' => ['required', 'integer', 'min:1'],
            'calories' => ['nullable', 'integer', 'min:0'],
            'difficulty' => ['required', \Illuminate\Validation\Rule::in(\App\Models\Recipe::DIFFICULTIES)],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                fn($a, $v, $fail) => (float) $v > 0 && (float) $v <= 100
                    ? $fail('Цена должна быть 0 (бесплатно) либо больше 100.')
                    : null,
            ],
            'category_id' => ['required', 'exists:categories,id'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required', \Illuminate\Validation\Rule::in(\App\Models\Ingredient::UNITS)],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.title' => ['required', 'string', 'max:40'],
            'steps.*.description' => ['required', 'string', 'max:255'],
            'images' => $recipe === null ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=500,min_height=500'],
        ]);
        $remaining =
            $recipe === null
                ? 0
                : $recipe
                    ->images()
                    ->whereNotIn('id', array_map('intval', (array) $request->input('delete_images', [])))
                    ->count();
        if ($remaining + count(array_filter((array) $request->file('images', []))) < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'У рецепта должно остаться хотя бы одно изображение.',
            ]);
        }
        return $data;
    }
    private function syncIngredients(\App\Models\Recipe $recipe, array $rows): void
    {
        $sync = [];
        foreach ($rows as $row) {
            if (!empty($row['ingredient_id'])) {
                $sync[(int) $row['ingredient_id']] = [
                    'quantity' => $row['quantity'] ?? 1,
                    'unit' => $row['unit'] ?? 'шт',
                ];
            }
        }
        $recipe->ingredients()->sync($sync);
    }
    private function syncSteps(\App\Models\Recipe $recipe, array $rows): void
    {
        $recipe->steps()->delete();
        foreach (array_values($rows) as $position => $row) {
            $recipe
                ->steps()
                ->create(['title' => $row['title'], 'description' => $row['description'], 'order' => $position + 1]);
        }
    }
    private function storeImages(\App\Models\Recipe $recipe, array $files, ?string $defaultKey, array $deleteIds): void
    {
        foreach ($recipe->images()->whereIn('id', array_map('intval', $deleteIds))->get() as $image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete([$image->path, $image->thumbnail]);
            $image->delete();
        }
        $created = [];
        foreach (array_values(array_filter($files)) as $index => $file) {
            $created[$index] = $this->storeImage($recipe, $file);
        }
        $recipe->load('images');
        $key = (string) $defaultKey;
        $selected = str_starts_with($key, 'existing_')
            ? $recipe->images->firstWhere('id', (int) substr($key, 9))
            : (str_starts_with($key, 'new_')
                ? $created[(int) substr($key, 4)] ?? null
                : null);
        $selected ??= $recipe->images->first();
        $recipe->images()->update(['is_default' => false]);
        $recipe
            ->images()
            ->whereKey($selected?->getKey())
            ->update(['is_default' => true]);
    }
    private function storeImage(
        \App\Models\Recipe $recipe,
        \Illuminate\Http\UploadedFile $file,
    ): \App\Models\RecipeImage {
        $dir = 'recipes/' . $recipe->getKey();
        $name =
            'original_' .
            \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)) .
            '.' .
            strtolower($file->getClientOriginalExtension() ?: 'jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->putFileAs($dir, $file, $name);
        return $recipe->images()->create([
            'path' => $dir . '/' . $name,
            'thumbnail' => $this->makeThumb($dir . '/' . $name, $recipe->title),
            'is_default' => false,
        ]);
    }
    private function makeThumb(string $original, string $title): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $src = match (strtolower(pathinfo($original, PATHINFO_EXTENSION))) {
            'png' => imagecreatefrompng($disk->path($original)),
            'webp' => imagecreatefromwebp($disk->path($original)),
            default => imagecreatefromjpeg($disk->path($original)),
        };
        [$sw, $sh] = [imagesx($src), imagesy($src)];
        $scale = min(350 / $sw, 200 / $sh);
        [$tw, $th] = [max(1, (int) round($sw * $scale)), max(1, (int) round($sh * $scale))];
        $canvas = imagecreatetruecolor(350, 200);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $src, intdiv(350 - $tw, 2), intdiv(200 - $th, 2), 0, 0, $tw, $th, $sw, $sh);
        imagedestroy($src);
        $font = resource_path('fonts/arial.ttf');
        $box = imagettfbbox(14, 0, $font, $title);
        [$bw, $bh] = [abs($box[2] - $box[0]), abs($box[7] - $box[1])];
        imagefilledrectangle($canvas, 338 - $bw, 190 - $bh, 349, 199, imagecolorallocatealpha($canvas, 0, 0, 0, 55));
        imagettftext($canvas, 14, 0, 342 - $bw, 194, imagecolorallocate($canvas, 255, 255, 255), $font, $title);
        $thumb =
            dirname($original) .
            '/thumb_350x200_' .
            \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)) .
            '.webp';
        imagewebp($canvas, $disk->path($thumb), 85);
        imagedestroy($canvas);
        return $thumb;
    }
    private function regenerateThumbs(\App\Models\Recipe $recipe): void
    {
        foreach ($recipe->images as $image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image->thumbnail);
            $image->update(['thumbnail' => $this->makeThumb($image->path, $recipe->title)]);
        }
    }
}
