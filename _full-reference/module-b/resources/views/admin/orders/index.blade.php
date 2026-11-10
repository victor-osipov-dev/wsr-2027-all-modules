@extends('admin.layouts.app')

@section('title', 'Заказы')

@section('content')
    <h5 class="mb-3">Заказы пользователей</h5>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Клиент</th>
                    <th>Рецепт</th>
                    <th>Стоимость</th>
                    <th>Статус оплаты</th>
                    <th>Дата</th>
                    <th class="text-end">Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>
                            <div>{{ $order->user?->email }}</div>
                            <div class="text-muted small">{{ $order->user?->name }}</div>
                        </td>
                        <td>{{ $order->recipe?->title ?? '—' }}</td>
                        <td>{{ number_format((float) $order->total_amount, 2, ',', ' ') }} ₽</td>
                        <td>
                            @php
                                $statusClass = match ($order->status) {
                                    'paid' => 'text-bg-success',
                                    'failed' => 'text-bg-danger',
                                    'expired' => 'text-bg-warning',
                                    default => 'text-bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $order->statusLabel() }}</span>
                        </td>
                        <td>{{ optional($order->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="text-end">
                            @if (in_array($order->status, ['failed', 'expired'], true))
                                <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Удалить заказ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                </form>
                            @else
                                <span class="text-muted small">удаление недоступно</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Заказы не найдены.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
@endsection
