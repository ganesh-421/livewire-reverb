<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Events\SwitchFlipped;

new class extends Component {
    public $toggleSwitch = false;

    public function mount()
    {
        $this->toggleSwitch = Cache::get('toggleSwitch', false);
    }

    // public function with()
    // {
    //     $this->toggleSwitch = Cache::get('toggleSwitch', false);
    //     return [
    //         'toggleSwitch' => $this->toggleSwitch,
    //     ];
    // }

    public function flipSwitch()
    {
        Cache::forever('toggleSwitch', $this->toggleSwitch);
        broadcast(new SwitchFlipped($this->toggleSwitch))->toOthers();
    }

    #[On('echo:switch,SwitchFlipped')]
    public function registerSwitchFlipped($payload)
    {
        $this->toggleSwitch = $payload['toggleSwitch'];
        Cache::forever('toggleSwitch', $this->toggleSwitch);
    }
}; ?>

<div x-data="{
    localToggle: @entangle('toggleSwitch'),
}" class="flex items-center justify-center min-h-screen">
    <label for="toggleSwitch" class="flex items-center cursor-pointer">
        <div class="relative">
            <input type="checkbox" id="toggleSwitch" class="sr-only" x-model="localToggle" x-on:change='$wire.flipSwitch'>
            <div class="block h-8 bg-gray-600 rounded-full w-14"></div>
            <div class="absolute w-6 h-6 transition-transform duration-200 rounded-full left-1 top-1"
                x-bind:class="localToggle ? 'translate-x-full bg-green-400' : 'bg-white'">
            </div>
        </div>
    </label>
</div>

</div>
