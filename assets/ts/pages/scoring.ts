// filepath: assets/ts/pages/scoring.ts

interface Player {
    id: number;
    name: string;
    remaining: number;
    lastDarts: number[];
}

interface GameData {
    id: number;
    start_score: number;
    single_in: boolean;
    double_in: boolean;
    single_out: boolean;
    double_out: boolean;
}

interface GameState {
    game: GameData;
    players: Player[];
    currentPlayer: number;
    currentDart: number;
}

let currentGameState: GameState | null = null;
let selectedScore = 0;
let selectedMultiplier = 1;

export function ScoringPage(): void {
    // Check if we're on the scoring page
    const playersListElement = document.getElementById('players-list');
    if (!playersListElement) {
        return; // Not on scoring page
    }

    // Initialize game state from the global variable
    if (typeof (window as any).gameState !== 'undefined') {
        currentGameState = (window as any).gameState;
        updateUI();
    }

    setupEventListeners();
    // Initialize UI with default multiplier
    selectedMultiplier = 1;
    updateScoreDisplay();
    updateButtonStates();
}

function setupEventListeners(): void {
    // Number buttons - auto-submit when clicked
    const numberButtons = document.querySelectorAll('[data-value]');
    numberButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            const value = parseInt(target.getAttribute('data-value') || '0');
            selectedScore = value;
            
            // Auto-submit the score immediately
            if (validateScore(selectedScore, selectedMultiplier)) {
                submitScore();
            } else {
                updateScoreDisplay(); // Show validation error
            }
        });
    });

    // Multiplier buttons - toggle on/off when clicked
    const multiplierButtons = document.querySelectorAll('[data-multiplier]');
    multiplierButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            const multiplier = parseInt(target.getAttribute('data-multiplier') || '1');
            
            // Toggle multiplier - if already selected, deselect it
            if (selectedMultiplier === multiplier) {
                selectedMultiplier = 1; // Reset to single (unselected state)
            } else {
                selectedMultiplier = multiplier;
            }
            
            updateScoreDisplay();
            updateButtonStates(); // Update button availability based on multiplier
        });
    });

    // Undo button
    const undoBtn = document.getElementById('undo-btn');
    if (undoBtn) {
        undoBtn.addEventListener('click', undoScore);
    }
}

function updateScoreDisplay(): void {
    // Highlight selected multiplier button
    const multiplierButtons = document.querySelectorAll('[data-multiplier]');
    multiplierButtons.forEach(button => {
        const multiplier = parseInt(button.getAttribute('data-multiplier') || '1');
        if (multiplier === selectedMultiplier && selectedMultiplier > 1) {
            button.classList.add('!bg-yellow-500');
        } else {
            button.classList.remove('!bg-yellow-500');
        }
    });

    // Show score preview
    const scorePreview = document.getElementById('score-preview') || createScorePreview();
    if (selectedMultiplier > 1) {
        scorePreview.textContent = `Selected: ${getMultiplierText(selectedMultiplier)} - Choose number`;
        scorePreview.className = 'text-lg font-bold text-blue-600 text-center mt-2';
    } else {
        scorePreview.textContent = 'Choose multiplier (or single), then number';
        scorePreview.className = 'text-lg font-bold text-gray-600 text-center mt-2';
    }
}

function updateButtonStates(): void {
    // Update number button states based on selected multiplier
    const numberButtons = document.querySelectorAll('[data-value]');
    numberButtons.forEach(button => {
        const value = parseInt(button.getAttribute('data-value') || '0');
        const isValid = validateScore(value, selectedMultiplier);
        
        if (!isValid) {
            button.classList.add('opacity-50', 'cursor-not-allowed');
            (button as HTMLButtonElement).disabled = true;
        } else {
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            (button as HTMLButtonElement).disabled = false;
        }
    });
}

function getMultiplierText(multiplier: number): string {
    switch (multiplier) {
        case 1: return 'Single';
        case 2: return 'Double';
        case 3: return 'Triple';
        default: return 'Single';
    }
}

function validateScore(score: number, multiplier: number): boolean {
    // No triple 25 (bull's eye)
    if (score === 25 && multiplier === 3) {
        return false;
    }
    
    // Valid ranges
    if (score < 0 || score > 25) {
        return false;
    }
    
    if (![1, 2, 3].includes(multiplier)) {
        return false;
    }
    
    return true;
}

function createScorePreview(): HTMLElement {
    const preview = document.createElement('div');
    preview.id = 'score-preview';
    preview.textContent = 'Select a score';
    preview.className = 'text-lg font-bold text-gray-400 text-center mt-2';
    
    const container = document.querySelector('.max-w-lg');
    if (container) {
        container.appendChild(preview);
    }
    
    return preview;
}

function updateUI(): void {
    if (!currentGameState) return;

    const playersListElement = document.getElementById('players-list');
    if (!playersListElement) return;

    // Update players display
    currentGameState.players.forEach((player, index) => {
        const playerElement = playersListElement.querySelector(`[data-player-index="${index}"]`) as HTMLElement;
        if (playerElement) {
            // Update remaining score
            const remainingElement = playerElement.querySelector('.remaining');
            if (remainingElement) {
                remainingElement.textContent = player.remaining.toString();
                
                // Color coding for remaining score
                if (player.remaining === 0) {
                    remainingElement.classList.add('text-green-600', 'font-bold');
                } else if (player.remaining <= 40) {
                    remainingElement.classList.add('text-orange-600', 'font-semibold');
                } else {
                    remainingElement.classList.remove('text-green-600', 'text-orange-600', 'font-bold', 'font-semibold');
                }
            }

            // Update last darts
            const dartsElement = playerElement.querySelector('.darts');
            if (dartsElement) {
                dartsElement.innerHTML = '';
                player.lastDarts.forEach(dart => {
                    const dartElement = document.createElement('span');
                    dartElement.textContent = dart.toString();
                    dartElement.className = 'dart-score px-1 py-0.5 text-xs bg-gray-200 rounded mr-1';
                    if (dart === 0) {
                        dartElement.classList.add('bg-red-200', 'text-red-800');
                    }
                    dartsElement.appendChild(dartElement);
                });
            }

            // Highlight current player
            if (index === currentGameState!.currentPlayer) {
                playerElement.classList.add('!bg-blue-200', 'border-2', 'border-blue-500');
                
                // Show current dart indicator
                const dartIndicator = playerElement.querySelector('.dart-indicator') || document.createElement('div');
                dartIndicator.className = 'dart-indicator text-sm font-bold text-blue-600';
                dartIndicator.textContent = `Dart ${currentGameState!.currentDart}/3`;
                if (!playerElement.querySelector('.dart-indicator')) {
                    playerElement.appendChild(dartIndicator);
                }
                
                // Show game rules hint for current player
                const rulesHint = playerElement.querySelector('.rules-hint') || document.createElement('div');
                rulesHint.className = 'rules-hint text-xs text-gray-600 mt-1';
                let hintText = '';
                
                if (currentGameState!.game.double_in && player.remaining === currentGameState!.game.start_score) {
                    hintText = 'Double in required';
                } else if (currentGameState!.game.double_out && player.remaining <= 50 && player.remaining > 1) {
                    hintText = 'Double out required';
                } else if (player.remaining === 1 && currentGameState!.game.double_out) {
                    hintText = 'Cannot finish on 1';
                }
                
                rulesHint.textContent = hintText;
                if (!playerElement.querySelector('.rules-hint')) {
                    playerElement.appendChild(rulesHint);
                }
            } else {
                playerElement.classList.remove('!bg-blue-200', 'border-2', 'border-blue-500');
                const dartIndicator = playerElement.querySelector('.dart-indicator');
                if (dartIndicator) {
                    dartIndicator.remove();
                }
                const rulesHint = playerElement.querySelector('.rules-hint');
                if (rulesHint) {
                    rulesHint.remove();
                }
            }
        }
    });
}

async function submitScore(): Promise<void> {
    if (!currentGameState || selectedScore === 0) return;

    try {
        const response = await fetch('/game/score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                score: selectedScore,
                multiplier: selectedMultiplier
            })
        });

        const result = await response.json();

        if (result.success) {
            currentGameState = result.gameState;
            updateUI();
            
            // Reset selection and multiplier
            selectedScore = 0;
            selectedMultiplier = 1;
            updateScoreDisplay();
            updateButtonStates();

            // Check for game over
            if (currentGameState && currentGameState.players.some(player => player.remaining === 0)) {
                const winner = currentGameState.players.find(player => player.remaining === 0);
                if (winner) {
                    alert(`🎉 ${winner.name} wins!`);
                    // Redirect to home or game creation
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                }
            }
        } else {
            alert(`Error: ${result.error}`);
        }
    } catch (error) {
        console.error('Error submitting score:', error);
        alert('Failed to submit score. Please try again.');
    }
}

async function undoScore(): Promise<void> {
    if (!currentGameState) return;

    try {
        const response = await fetch('/game/score/undo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success) {
            currentGameState = result.gameState;
            updateUI();
        } else {
            alert(`Error: ${result.error}`);
        }
    } catch (error) {
        console.error('Error undoing score:', error);
        alert('Failed to undo score. Please try again.');
    }
}
