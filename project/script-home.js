// Dating Cards Navigation Script
let currentIndex = 0;
let cards = [];
let totalPartners = 0;

function initializeCards() {
    cards = document.querySelectorAll('.dating-card');
    totalPartners = cards.length;
    
    if (totalPartners === 0) {
        return;
    }
    
    updateDisplay();
    setupEventListeners();
}

function setupEventListeners() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const likeBtn = document.getElementById('likeBtn');
    const dislikeBtn = document.getElementById('dislikeBtn');

    if (prevBtn) prevBtn.addEventListener('click', showPrevious);
    if (nextBtn) nextBtn.addEventListener('click', showNext);
    if (likeBtn) likeBtn.addEventListener('click', () => reactToPartner('like'));
    if (dislikeBtn) dislikeBtn.addEventListener('click', () => reactToPartner('dislike'));

    // Keyboard navigation
    document.addEventListener('keydown', handleKeyboard);
}

function showNext() {
    if (currentIndex < totalPartners - 1) {
        currentIndex++;
        updateDisplay();
    }
}

function showPrevious() {
    if (currentIndex > 0) {
        currentIndex--;
        updateDisplay();
    }
}

function updateDisplay() {
    // Verstecke alle Karten
    cards.forEach((card, index) => {
        card.classList.remove('active');
        if (index === currentIndex) {
            card.classList.add('active');
        }
    });

    // Aktualisiere Progress-Info
    const currentIndexElement = document.getElementById('currentIndex');
    if (currentIndexElement) {
        currentIndexElement.textContent = currentIndex + 1;
    }

    // Aktualisiere Button-Status
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (prevBtn) {
        prevBtn.disabled = currentIndex === 0;
    }
    if (nextBtn) {
        nextBtn.disabled = currentIndex === totalPartners - 1;
    }
}

function reactToPartner(reaction) {
    if (currentIndex >= cards.length) return;

    const partnerId = cards[currentIndex].getAttribute('data-id');
    
    // Sende Reaction zum Server
    fetch('../api/reaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            partner_id: partnerId,
            reaction: reaction
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Zeige Feedback an
            showFeedback(reaction);
            
            // Gehe zum nächsten Partner
            if (currentIndex < totalPartners - 1) {
                setTimeout(() => {
                    showNext();
                }, 500);
            } else {
                // Keine mehr Partner
                showNoMorePartners();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function showFeedback(reaction) {
    const card = cards[currentIndex];
    const feedbackClass = reaction === 'like' ? 'liked' : 'disliked';
    
    card.classList.add('animated');
    card.classList.add(feedbackClass);
    
    // Entferne Animation nach Abschluss
    setTimeout(() => {
        card.classList.remove('animated');
        card.classList.remove(feedbackClass);
    }, 600);
}

function showNoMorePartners() {
    const container = document.querySelector('.dating-cards-container');
    if (container) {
        const message = document.createElement('div');
        message.className = 'no-more-partners';
        message.innerHTML = '<p>Keine weiteren Partner verfügbar. Komm später zurück!</p>';
        container.appendChild(message);
        
        // Deaktiviere Buttons
        document.getElementById('likeBtn').disabled = true;
        document.getElementById('dislikeBtn').disabled = true;
        document.getElementById('nextBtn').disabled = true;
    }
}

function handleKeyboard(event) {
    switch(event.key) {
        case 'ArrowLeft':
            showPrevious();
            break;
        case 'ArrowRight':
            showNext();
            break;
        case 'Enter':
            document.getElementById('likeBtn').click();
            break;
        case 'Delete':
        case 'Backspace':
            document.getElementById('dislikeBtn').click();
            break;
    }
}

// Initialisiere bei Seitenladestart
document.addEventListener('DOMContentLoaded', initializeCards);
