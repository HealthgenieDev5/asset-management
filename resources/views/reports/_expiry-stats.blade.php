{{--
  Expiry summary stats banner.
  Props: $statExpired (int), $stat30 (int), $stat90 (int)
--}}
@if (($statExpired + $stat30 + $stat90) > 0)
<div class="mb-5 grid grid-cols-3 gap-3 print:hidden">
    <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/40 dark:bg-red-900/20">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
            <svg class="size-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ $statExpired }}</div>
            <div class="text-xs font-medium text-red-500 dark:text-red-400">Expired</div>
        </div>
    </div>

    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-900/20">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
            <svg class="size-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $stat30 }}</div>
            <div class="text-xs font-medium text-amber-500 dark:text-amber-400">Due in 30 days</div>
        </div>
    </div>

    <div class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-900/40 dark:bg-blue-900/20">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
            <svg class="size-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>
        <div>
            <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ $stat90 }}</div>
            <div class="text-xs font-medium text-blue-500 dark:text-blue-400">Due in 90 days</div>
        </div>
    </div>
</div>
@endif
