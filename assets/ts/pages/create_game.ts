export function CreateGamePage(): void {
    const playersContainer = document.getElementById('players-container') as HTMLElement | null;
    const addPlayerBtn = document.getElementById('add-player-btn') as HTMLButtonElement | null;

    if (!playersContainer || !addPlayerBtn) {
        // If essential elements are not on the page, don't run the script
        return;
    }

    let playerCount = playersContainer.querySelectorAll('.player-input-group').length;

    function toggleRemoveButtons(): void {
        const removeButtons = playersContainer!.querySelectorAll('.remove-player-btn') as NodeListOf<HTMLButtonElement>;
        removeButtons.forEach(btn => {
            btn.disabled = playerCount <= 1;
        });
    }

    function addPlayerInput(): void {
        const newPlayerInput = `
            <div class="flex items-center space-x-2 player-input-group">
                <input type="text" name="players[]" placeholder="Player ${playerCount} Name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                <button type="button" class="remove-player-btn text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
        `;
        playersContainer!.insertAdjacentHTML('beforeend', newPlayerInput);
    }

    addPlayerBtn.addEventListener('click', function () {
        playerCount++;
        addPlayerInput();
        toggleRemoveButtons();
    });

    playersContainer.addEventListener('click', function (e: MouseEvent) {
        const target = e.target as HTMLElement;
        const removeButton = target.closest('.remove-player-btn') as HTMLButtonElement | null;

        if (removeButton) {
            const playerInputGroup = target.closest('.player-input-group') as HTMLElement | null;
            if (playerInputGroup) {
                playerInputGroup.remove();
                playerCount--;
                const playerInputs = playersContainer!.querySelectorAll('input[name="players[]"]') as NodeListOf<HTMLInputElement>;
                playerInputs.forEach((input, index) => {
                    input.placeholder = `Player ${index + 1} Name`;
                });
                toggleRemoveButtons();
            }
        }
    });

    // Initial check for remove buttons
    addPlayerInput();
    toggleRemoveButtons();
}

