@extends('layouts.app')

@section('title', 'Notifications — Partner Portal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Notifications</h1>
            <p class="text-xs text-slate-500 mt-0.5">Stay updated on your withdrawal requests, lead conversions, and commission disbursements.</p>
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('partner.notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    Mark All as Read ({{ $unreadCount }})
                </button>
            </form>
        @endif
    </div>

    <!-- Notifications List Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $n)
                <div class="p-5 flex items-start space-x-4 transition hover:bg-slate-50/75 {{ ! $n->is_read ? 'bg-tg-purple-light/20' : '' }}">
                    <!-- Icon based on type -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-sm font-bold
                        {{ $n->type === 'success' ? 'bg-tg-green-light text-tg-green' : ($n->type === 'error' ? 'bg-rose-100 text-rose-700' : ($n->type === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-tg-purple-light text-tg-purple')) }}">
                        @if($n->type === 'success')
                            ✓
                        @elseif($n->type === 'error')
                            ✕
                        @elseif($n->type === 'warning')
                            !
                        @else
                            ℹ
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-xs font-bold text-tg-dark flex items-center space-x-2">
                                <span>{{ $n->title }}</span>
                                @if(! $n->is_read)
                                    <span class="w-2 h-2 rounded-full bg-tg-purple flex-shrink-0"></span>
                                @endif
                            </h3>
                            <span class="text-[11px] text-slate-400 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $n->message }}</p>

                        @if($n->link)
                            <div class="mt-2.5">
                                <a href="{{ $n->link }}" class="text-xs font-bold text-tg-purple hover:underline inline-flex items-center space-x-1">
                                    <span>View Details</span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    @if(! $n->is_read)
                        <form action="{{ route('partner.notifications.read', $n) }}" method="POST" class="flex-shrink-0">
                            @csrf
                            <button type="submit" title="Mark as read" class="p-1.5 text-slate-400 hover:text-tg-purple transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center text-slate-400">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <p class="font-bold text-slate-600 text-sm">No notifications yet</p>
                    <p class="text-xs text-slate-400 mt-1">You will receive notifications when withdrawal requests are reviewed and payouts are dispatched.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
