// Funktion zum Erstellen des Hero-Card Contents
function renderContent() {
    const app = document.getElementById('app');
    
    app.innerHTML = `
        <section class="hero-card">
            <img id="logo" src="./img/blinder_logo.png" alt="DateFinder Logo">
            <h1>Ace of Dates</h1>
            <p class="tagline">Compatibility before appearance</p>
            <div class="actions">
            <a href="./subPages/register.php"><div class="btn btn-primary">Registrieren</div></a>
                
            <a href="./subPages/login.php"><div class="btn btn-secondary">Login</div></a>
            </div>
        </section>
    `;
    //user password: userpassword
        
    // Mascot hinzufügen
    let mascotLink = document.createElement('a');
    mascotLink.href = './subPages/info.html';
    mascotLink.innerHTML = '<img class="mascot" src="./img/maskotchen.png" alt="Maskottchen">';
    document.body.appendChild(mascotLink);
}

/*Funktion zum Wechseln des Contents
function switchContent(contentType) {
    const app = document.getElementById('app');
    
    switch(contentType) {
        case 'home':
            renderContent();
            break;
        case 'register':
            window.open('./subPages/register.php');
            break;
        case 'login':
            app.innerHTML = `
                <section class="content-section">
                    <h2>Login</h2>
                    <p>Loginseite wird hier angezeigt</p>
                </section>
            `;
            break;
        default:
            renderContent();
    }
}
*/

// Content beim Laden der Seite erstellen
window.addEventListener('DOMContentLoaded', renderContent);
