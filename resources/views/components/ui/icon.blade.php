@props(['name'=>'grid'])
@php($paths=[
'grid'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
'users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
'queue'=>'M8 2v4 M16 2v4 M3 10h18 M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2 M8 14h3 M8 18h6',
'pulse'=>'M2 12h5l3-8 4 16 3-8h5',
'pill'=>'M9 15l6-6 M5 19a5 5 0 0 1 0-7l7-7a5 5 0 0 1 7 7l-7 7a5 5 0 0 1-7 0',
'box'=>'M3 7l9-5 9 5v10l-9 5-9-5z M3 7l9 5 9-5 M12 12v10 M7.5 4.5l9 5',
'wallet'=>'M3 5h16v4 M3 5v14h18V9H3 M16 13h5 M17 15h.01',
'chart'=>'M4 3v18h17 M8 16v-5 M13 16V7 M18 16v-9',
'settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2',
'shield'=>'M12 2l9 4v6c0 5-9 10-9 10S3 17 3 12V6z M8 12l3 3 5-6',
'search'=>'M21 21l-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
'arrow'=>'M5 12h14 M14 7l5 5-5 5','plus'=>'M12 5v14 M5 12h14','clock'=>'M12 8v5l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
'bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4','menu'=>'M3 6h18 M3 12h18 M3 18h18','logout'=>'M9 4H3v16h6 M10 12h11 M17 8l4 4-4 4','check'=>'M5 12l4 4L19 6'
])
<svg {{ $attributes->merge(['class'=>'h-5 w-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}"/></svg>
