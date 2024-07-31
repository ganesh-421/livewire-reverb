<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;
use App\Events\SwitchFlipped;
use App\Events\CursorMoved;

new class extends Component {
    public $toggleSwitch = false;

    #[Locked]
    public $userId;

    #[Locked]
    public $activeUsersCount = 0;

    #[Locked]
    public $userColors = [];

    #[Locked]
    public $cursorPositions = [];

    public function mount()
    {
        if (!Session::has('user_id')) {
            $this->userId = uniqid('user_', true);
            Session::put('user_id', $this->userId);
        } else {
            $this->userId = Session::get('user_id');
        }
        $this->toggleSwitch = Cache::get('toggleSwitch', false);
        $this->userColors[$this->userId] = $this->generateRandomColor();
        $this->updateActiveUsersCount();
    }

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

    #[On('echo:mouse-movement,CursorMoved')]
    public function registerCursorMoved($payload)
    {
        if ($payload['position'] !== null) {
            $this->cursorPositions[$payload['userId']] = $payload['position'];
            if (!isset($this->userColors[$payload['userId']])) {
                $this->userColors[$payload['userId']] = $this->generateRandomColor();
            }
        } else {
            unset($this->cursorPositions[$payload['userId']]);
        }
        $this->updateActiveUsersCount();
    }

    public function moveMouse($cursorPosition)
    {
        $payload = [
            'userId' => $this->userId,
            'position' => $cursorPosition,
            'color' => $this->userColors[$this->userId],
        ];

        broadcast(new CursorMoved($payload))->toOthers();
    }

    public function updateActiveUsersCount()
    {
        $this->activeUsersCount = count($this->cursorPositions) + 1;
    }

    public function generateRandomColor()
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xffffff)), 6, '0', STR_PAD_LEFT);
    }

    public function setInactive()
    {
        unset($this->cursorPositions[$this->userId]);
        $this->updateActiveUsersCount();
        broadcast(
            new CursorMoved([
                'userId' => $this->userId,
                'position' => null,
                'color' => null,
            ]),
        )->toOthers();
    }
}; ?>

<div x-data="{
    localToggle: @entangle('toggleSwitch'),
    cursors: @entangle('cursorPositions'),
    smoothCursors: {},
    cursorSpeed: 0.2,
    init() {
        this.$watch('cursors', (value) => {
            this.updateSmoothCursors(value);
        });
        this.animateCursors();
    },
    updateSmoothCursors(newCursors) {
        for (let userId in this.smoothCursors) {
            if (!newCursors[userId]) {
                delete this.smoothCursors[userId];
            }
        }
        for (let userId in newCursors) {
            if (!this.smoothCursors[userId] && newCursors[userId]) {
                this.smoothCursors[userId] = { ...newCursors[userId], active: true };
            } else if (this.smoothCursors[userId] && newCursors[userId]) {
                this.smoothCursors[userId].active = true;
            }
        }
    },
    animateCursors() {
        for (let userId in this.smoothCursors) {
            if (this.cursors[userId] && this.smoothCursors[userId].active) {
                let target = this.cursors[userId];
                let current = this.smoothCursors[userId];

                current.x += (target.x - current.x) * this.cursorSpeed;
                current.y += (target.y - current.y) * this.cursorSpeed;
            }
        }
        requestAnimationFrame(() => this.animateCursors());
    }
}">
    <div class="flex items-center justify-center min-h-screen">
        <label for="toggleSwitch" class="flex items-center cursor-pointer">
            <div class="relative">
                <input type="checkbox" id="toggleSwitch" class="sr-only" x-model="localToggle"
                    x-on:change='$wire.flipSwitch'>
                <div class="block h-8 bg-gray-600 rounded-full w-14"></div>
                <div class="absolute w-6 h-6 transition-transform duration-200 rounded-full left-1 top-1"
                    x-bind:class="localToggle ? 'translate-x-full bg-green-400' : 'bg-white'">
                </div>
            </div>
        </label>
    </div>
    @foreach ($cursorPositions as $position)
        <div class="fixed bottom-0 right-0 p-4 text-white bg-black bg-opacity-50 rounded-tl-lg">
            Active Users: {{ $activeUsersCount }}
        </div>
    @endforeach
</div>
