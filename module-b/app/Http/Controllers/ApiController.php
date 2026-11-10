<?php

namespace App\Http\Controllers;

class ApiController extends \Illuminate\Routing\Controller
{
    private const SORTS = [
        'newest_desc' => ['created_at', 'desc'],
        'newest_asc' => ['created_at', 'asc'],
        'time_asc' => ['cooking_time', 'asc'],
        'time_desc' => ['cooking_time', 'desc'],
    ];
    private const PASSWORDS = [
        '/^[A-Za-z0-9#_\-$.]+$/' => 'The password may contain only latin letters, digits and special characters',
        '/[a-z]/' => 'The password must contain at least one lowercase letter',
        '/[A-Z]/' => 'The password must contain at least one uppercase letter',
        '/[#_\-$.]/' => 'The password must contain at least one special character (# _ - $ .)',
    ];

    public function register(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(
            [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => [
                    'required',
                    'string',
                    'min:5',
                    function ($attribute, $value, $fail) {
                        foreach (self::PASSWORDS as $pattern => $message) {
                            if (!preg_match($pattern, (string) $value)) {
                                $fail($message);
                            }
                        }
                    },
                ],
            ],
            [
                'email.required' => 'The email field is required',
                'email.email' => 'The email must be a valid email address',
                'email.unique' => 'The email has already been taken',
                'password.required' => 'The password field is required',
                'password.min' => 'The password must be at least 5 characters',
            ],
        );
        \App\Models\User::create([
            'name' => explode('@', (string) $request->input('email'))[0],
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
            'role' => 'user',
        ]);
        return response()->json(['success' => true], 201);
    }
    public function login(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(
            ['email' => ['required', 'string'], 'password' => ['required', 'string']],
            [
                'email.required' => 'The email field is required',
                'password.required' => 'The password field is required',
            ],
        );
        $user = \App\Models\User::where('email', $request->input('email'))->first();
        if (
            $user === null ||
            !\Illuminate\Support\Facades\Hash::check((string) $request->input('password'), $user->password)
        ) {
            return response()->json(
                ['message' => 'Invalid data', 'errors' => ['email' => ['Invalid credentials']]],
                422,
            );
        }
        return response()->json(['token' => $user->createToken('api-token')->plainTextToken], 200);
    }
    public function webhook(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $purchase = $this->findPurchase((string) $request->input('purchase_id', ''));
        if ($purchase === null) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }
        if ($purchase->status === 'paid') {
            return response()->json(['message' => 'Recipe already purchased'], 409);
        }
        if ($purchase->status === 'failed') {
            return $this->paymentError();
        }
        $card = (string) $request->input('card', '');
        if (
            ($card !== '' && $card !== '123456789') ||
            in_array(
                strtolower((string) $request->input('status', '')),
                ['failed', 'error', 'declined', 'cancelled'],
                true,
            )
        ) {
            $purchase->update(['status' => 'failed']);
            return $this->paymentError();
        }
        if ($purchase->expires_at !== null && $purchase->expires_at->isPast()) {
            $purchase->update(['status' => 'expired']);
            return response()->json(['message' => 'Time is left'], 419);
        }
        $purchase->update(['status' => 'paid', 'paid_at' => now(), 'expires_at' => null]);
        return response()->json(['message' => 'Success'], 200);
    }
    public function recipes(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 5), 100));
        [$column, $direction] =
            self::SORTS[(string) $request->input('sort', 'newest_desc')] ?? self::SORTS['newest_desc'];
        $f = $request->only(['category_id', 'difficulty', 'time_min', 'time_max', 'price_min', 'price_max']);
        $p = \App\Models\Recipe::with(['category', 'images'])
            ->when(!empty($f['category_id']), fn($q) => $q->where('category_id', $f['category_id']))
            ->when(!empty($f['difficulty']), fn($q) => $q->where('difficulty', $f['difficulty']))
            ->when(filled($f['time_min'] ?? null), fn($q) => $q->where('cooking_time', '>=', (int) $f['time_min']))
            ->when(filled($f['time_max'] ?? null), fn($q) => $q->where('cooking_time', '<=', (int) $f['time_max']))
            ->when((float) ($f['price_min'] ?? 0) > 0, fn($q) => $q->where('price', '>=', (float) $f['price_min']))
            ->when((float) ($f['price_max'] ?? 0) > 0, fn($q) => $q->where('price', '<=', (float) $f['price_max']))
            ->orderBy($column, $direction)
            ->paginate($perPage)
            ->appends($request->query());
        return response()->json(
            [
                'current_page' => $p->currentPage(),
                'data' => collect($p->items())->map(fn($r) => $this->listRecipe($r))->values(),
                'first_page_url' => $p->url(1),
                'from' => $p->firstItem(),
                'last_page' => $p->lastPage(),
                'last_page_url' => $p->url($p->lastPage()),
                'next_page_url' => $p->nextPageUrl(),
                'path' => $p->path(),
                'per_page' => $p->perPage(),
                'prev_page_url' => $p->previousPageUrl(),
                'to' => $p->lastItem(),
                'total' => $p->total(),
            ],
            200,
        );
    }
    public function recipe(int $id): \Illuminate\Http\JsonResponse
    {
        $recipe = \App\Models\Recipe::with(['category', 'images', 'ingredients', 'steps'])->find($id);
        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }
        return response()->json($this->detailRecipe($recipe), 200);
    }
    public function favoriteStore(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['recipe_id' => ['required', 'integer']]);
        $recipe = \App\Models\Recipe::find($request->integer('recipe_id'));
        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }
        if (\App\Models\Favorite::where('user_id', $request->user()->id)->where('recipe_id', $recipe->id)->exists()) {
            return response()->json(['message' => 'Recipe already in favorites'], 409);
        }
        \App\Models\Favorite::create(['user_id' => $request->user()->id, 'recipe_id' => $recipe->id]);
        return response()->json(['success' => true, 'message' => 'Recipe added to favorites'], 201);
    }
    public function favoriteIndex(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $recipes = \App\Models\Favorite::where('user_id', $request->user()->id)
            ->whereHas('recipe')
            ->with('recipe.images')
            ->latest()
            ->get()
            ->map(fn($f) => $this->listRecipe($f->recipe) + ['favorited_at' => optional($f->created_at)->toJSON()])
            ->values();
        return response()->json($recipes, 200);
    }
    public function favoriteDestroy(\Illuminate\Http\Request $request, int $recipeId): \Illuminate\Http\JsonResponse
    {
        $favorite = \App\Models\Favorite::where('user_id', $request->user()->id)
            ->where('recipe_id', $recipeId)
            ->first();
        if ($favorite === null) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }
        $favorite->delete();
        return response()->json(['success' => true, 'message' => 'Recipe removed from favorites'], 200);
    }
    public function purchaseStore(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['recipe_id' => ['required', 'integer']]);
        $recipe = \App\Models\Recipe::find($request->integer('recipe_id'));
        if ($recipe === null) {
            return response()->json(['message' => 'Recipe not found'], 404);
        }
        if ((float) $recipe->price <= 0) {
            return response()->json(['message' => 'This recipe is free'], 400);
        }
        $purchase = \App\Models\Purchase::create([
            'user_id' => $request->user()->id,
            'recipe_id' => $recipe->id,
            'total_amount' => $recipe->price,
            'status' => 'pending',
            'expires_at' => now()->addMinute(),
            'order_number' => $this->orderNumber(),
        ]);
        return response()->json(
            [
                'purchase_id' => $purchase->id,
                'status' => $purchase->status,
                'payment_link' =>
                    url('pay') .
                    '?order_id=' .
                    $purchase->order_number .
                    '&url=' .
                    urlencode((string) route('api.webhook')),
                'expires_at' => optional($purchase->expires_at)->toJSON(),
            ],
            201,
        );
    }
    public function purchases(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $purchases = \App\Models\Purchase::where('user_id', $request->user()->id)
            ->with('recipe.images')
            ->latest()
            ->get()
            ->map(
                fn($p) => [
                    'id' => $p->id,
                    'recipe' => [
                        'id' => $p->recipe->id,
                        'title' => $p->recipe->title,
                        'price' => (float) $p->recipe->price,
                        'default_image' => $p->recipe->default_image_url,
                    ],
                    'total_amount' => (float) $p->total_amount,
                    'status' => $p->status,
                    'paid_at' => optional($p->paid_at)->toJSON(),
                    'expires_at' => optional($p->expires_at)->toJSON(),
                    'created_at' => optional($p->created_at)->toJSON(),
                ],
            )
            ->values();
        return response()->json($purchases, 200);
    }
    public function purchaseShow(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $purchase = \App\Models\Purchase::where('user_id', $request->user()->id)->with('recipe.images')->find($id);
        if ($purchase === null) {
            return response()->json(['message' => 'Purchase not found'], 404);
        }
        $recipe = $purchase->recipe;
        return response()->json(
            [
                'id' => $purchase->id,
                'recipe' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'description' => $recipe->description,
                    'price' => (float) $recipe->price,
                    'difficulty' => $recipe->difficulty,
                    'cooking_time' => $recipe->cooking_time,
                    'formatted_time' => $recipe->formatted_time,
                    'steps' => $recipe->steps
                        ->map(
                            fn($s) => [
                                'id' => $s->id,
                                'title' => $s->title,
                                'description' => $s->description,
                                'order' => (int) $s->order,
                                'is_completed' => false,
                                'completed_at' => null,
                            ],
                        )
                        ->values(),
                ],
                'status' => $purchase->status,
                'paid_at' => optional($purchase->paid_at)->toJSON(),
                'created_at' => optional($purchase->created_at)->toJSON(),
            ],
            200,
        );
    }
    private function listRecipe(\App\Models\Recipe $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'cooking_time' => $r->cooking_time,
            'calories' => $r->calories,
            'difficulty' => $r->difficulty,
            'price' => (float) $r->price,
            'category' => $r->category ? ['id' => $r->category->id, 'name' => $r->category->name] : null,
            'default_image' => $r->default_image_url,
            'formatted_time' => $r->formatted_time,
            'created_at' => optional($r->created_at)->toJSON(),
        ];
    }
    private function detailRecipe(\App\Models\Recipe $r): array
    {
        return $this->listRecipe($r) + [
            'images' => $r->images
                ->map(
                    fn($i) => [
                        'id' => $i->id,
                        'path' => '/storage/' . $i->path,
                        'thumbnail' => '/storage/' . $i->thumbnail,
                        'is_default' => (bool) $i->is_default,
                    ],
                )
                ->values(),
            'ingredients' => $r->ingredients
                ->map(
                    fn($i) => [
                        'id' => $i->id,
                        'title' => $i->title,
                        'pivot' => ['quantity' => (float) $i->pivot->quantity, 'unit' => $i->pivot->unit],
                    ],
                )
                ->values(),
            'steps' => $r->steps
                ->map(
                    fn($s) => [
                        'id' => $s->id,
                        'title' => $s->title,
                        'description' => $s->description,
                        'order' => (int) $s->order,
                    ],
                )
                ->values(),
        ];
    }
    private function findPurchase(string $paymentId): ?\App\Models\Purchase
    {
        $paymentId = strtolower(trim($paymentId));
        if ($paymentId === '') {
            return null;
        }
        $purchase = \App\Models\Purchase::whereNotNull('order_number')
            ->get()
            ->first(fn($p) => md5((string) $p->order_number) === $paymentId);
        return $purchase ?? (ctype_digit($paymentId) ? \App\Models\Purchase::find((int) $paymentId) : null);
    }
    private function orderNumber(): string
    {
        do {
            $number = (string) random_int(100000, 9999999);
        } while (\App\Models\Purchase::where('order_number', $number)->exists());
        return $number;
    }
    private function paymentError(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'You cannot purchase an order if a payment error occurs'], 408);
    }
}
