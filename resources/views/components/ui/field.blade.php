@props(['label','model','type'=>'text','required'=>false])
<label class="block"><span class="label">{{ $label }} 
@if($required)<span class="text-teal-600">*</span>
@endif</span>
@if($type==='textarea')<textarea wire:model="{{ $model }}" {{ $attributes->class('field') }} rows="3"></textarea>
@else<input type="{{ $type }}" wire:model="{{ $model }}" {{ $attributes->class('field') }}>
@endif</label>
