<?php
require_once __DIR__ . '/../../config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen - Rejoignez la communauté</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #1F3D2B;
            --sand: #F2E8CF;
            --violet: #5B3E96;
            --blue: #3A86C4;
            --bg: #0a1a10;
            --surface: #0f2318;
            --text: #F2E8CF;
            --muted: #a8b8a0;
            --card-bg: rgba(15, 35, 24, 0.72);
            --glass: rgba(31, 61, 43, 0.45);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            --radius: 18px;
            --tr: 0.4s cubic-bezier(.4, 0, .2, 1);
        }
        [data-theme="light"] {
            --green: #2d5a3f;
            --bg: #F2E8CF;
            --surface: #ffffff;
            --text: #1F3D2B;
            --muted: #5a6b5a;
            --card-bg: rgba(255, 255, 255, 0.9);
            --glass: rgba(45, 90, 63, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); font-family: 'Lato', sans-serif; transition: all var(--tr); color: var(--text); }
        h1, h2, h3, h4, h5, h6, .navbar-brand { font-family: 'Cormorant Garamond', serif; font-weight: 700; }
        .navbar-custom { background: var(--surface); backdrop-filter: blur(10px); box-shadow: var(--shadow); padding: 1rem 0; position: fixed; top: 0; width: 100%; z-index: 1000; }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; color: var(--green) !important; }
        .navbar-brand i { color: var(--violet); margin-right: 10px; }
        .nav-link { color: var(--text) !important; font-weight: 500; }
        .nav-link:hover { color: var(--violet) !important; }
        .theme-toggle { background: var(--green); border: none; border-radius: 50px; padding: 8px 16px; color: var(--sand); cursor: pointer; transition: all var(--tr); }
        .theme-toggle:hover { background: var(--violet); transform: scale(1.05); }
        .hero { position: relative; background: linear-gradient(135deg, rgba(31,61,43,0.85), rgba(31,61,43,0.75)), url('https://images.pexels.com/photos/459225/pexels-photo-459225.jpeg?auto=compress&cs=tinysrgb&w=1920'); background-size: cover; background-position: center; background-attachment: fixed; color: var(--sand); padding: 140px 0; text-align: center; margin-top: 76px; }
        .hero::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 100px; background: linear-gradient(to top, var(--bg), transparent); }
        .hero h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 20px; }
        .hero h1 span { color: var(--violet); }
        .hero p { font-size: 1.2rem; max-width: 600px; margin: 0 auto; }
        .form-card { background: var(--card-bg); backdrop-filter: blur(10px); border-radius: var(--radius); box-shadow: var(--shadow); padding: 45px; transition: all var(--tr); border: 1px solid var(--glass); margin-top: 30px; }
        .form-card:hover { transform: translateY(-5px); }
        .form-card h3 { color: var(--green); text-align: center; margin-bottom: 30px; }
        .form-card h3:after { content: ''; display: block; width: 60px; height: 3px; background: var(--violet); margin: 10px auto 0; border-radius: 2px; }
        .form-label { font-weight: 600; color: var(--text); margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid var(--glass); padding: 12px 15px; background-color: var(--surface); color: var(--text); }
        .form-control:focus, .form-select:focus { border-color: var(--violet); box-shadow: 0 0 0 3px rgba(91,62,150,0.2); outline: none; }
        .btn-participer { background: var(--green); color: var(--sand); font-weight: 600; padding: 14px 50px; border-radius: 50px; border: none; font-size: 1.1rem; transition: all var(--tr); width: 100%; }
        .btn-participer:hover { background: var(--violet); transform: scale(1.02); }
        .footer { background: var(--surface); color: var(--muted); padding: 50px 0 30px; margin-top: 60px; text-align: center; border-top: 1px solid var(--glass); }
        .footer h5 { color: var(--green); }
        .footer a { color: var(--muted); transition: color var(--tr); text-decoration: none; }
        .footer a:hover { color: var(--violet); }
        .social-icons a { margin: 0 10px; font-size: 1.3rem; display: inline-block; }
        hr { border-color: var(--glass); }
        .text-muted { color: var(--muted) !important; }
    </style>
</head>
<body data-theme="dark">
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-leaf"></i>GaiaLumen</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="#form">Participer</a></li>
                <li class="nav-item"><a class="nav-link" href="#values">Nos valeurs</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <li class="nav-item"><button class="theme-toggle" id="themeToggle"><i class="fas fa-sun me-1"></i> Mode clair</button></li>
            </ul>
        </div>
    </div>
</nav>
<section class="hero">
    <div class="container">
        <h1>Participez aux événements<br>pour une planète <span>plus verte</span></h1>
        <p>Rejoignez une communauté engagée pour un avenir durable et harmonieux</p>
    </div>
</section>
<div class="container my-5" id="form">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="text-center mb-4">
                    <i class="fas fa-seedling fa-3x" style="color: var(--violet);"></i>
                    <h3>🌿 Rejoignez la communauté Gaia</h3>
                    <p class="text-muted">Inscrivez-vous pour participer à nos prochains événements</p>
                </div>
                <form method="POST" action="participation/add.php">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="nom_complet" class="form-control" placeholder="Votre nom et prénom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="votre@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" placeholder="Votre numéro">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Événement *</label>
                            <select name="id_event" class="form-select" required>
                                <option value="">-- Choisir un événement --</option>
                                <?php
                                $db = config::getConnexion();
                                $events = $db->query("SELECT id_event, titre, date FROM evenement ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($events as $e): ?>
                                    <option value="<?= $e['id_event'] ?>"><?= htmlspecialchars($e['titre']) ?> (<?= date('d/m/Y', strtotime($e['date'])) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Centre d'intérêt</label>
                            <select name="centre_interet" class="form-select">
                                <option value="Ateliers pratiques">🌱 Ateliers pratiques</option>
                                <option value="Repas / Cuisine">🍲 Repas / Cuisine</option>
                                <option value="Yoga & Bien-être">🧘 Yoga & Bien-être</option>
                                <option value="Nutrition">🥗 Nutrition</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mt-5">
                        <button type="submit" class="btn-participer"><i class="fas fa-leaf"></i> Devenir participant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<section class="values-section" id="values" style="background: var(--surface); padding: 60px 0; margin-top: 40px;">
    <div class="container">
        <div class="text-center mb-5">
            <h3 style="color: var(--green); font-weight: 700;">Nos engagements</h3>
            <p class="text-muted">Des valeurs qui nous guident au quotidien</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4"><div class="value-card" style="text-align: center; padding: 30px; border-radius: var(--radius); background: var(--card-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass);"><i class="fas fa-globe-africa" style="font-size: 2.5rem; color: var(--violet); margin-bottom: 15px;"></i><h5 style="color: var(--green);">Écologie</h5><p style="color: var(--muted);">Agir pour la protection de notre planète</p></div></div>
            <div class="col-md-4"><div class="value-card" style="text-align: center; padding: 30px; border-radius: var(--radius); background: var(--card-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass);"><i class="fas fa-hand-holding-heart" style="font-size: 2.5rem; color: var(--violet); margin-bottom: 15px;"></i><h5 style="color: var(--green);">Solidarité</h5><p style="color: var(--muted);">Construire une communauté bienveillante</p></div></div>
            <div class="col-md-4"><div class="value-card" style="text-align: center; padding: 30px; border-radius: var(--radius); background: var(--card-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass);"><i class="fas fa-chalkboard-user" style="font-size: 2.5rem; color: var(--violet); margin-bottom: 15px;"></i><h5 style="color: var(--green);">Éducation</h5><p style="color: var(--muted);">Transmettre les savoirs pour demain</p></div></div>
        </div>
    </div>
</section>
<footer class="footer" id="contact">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4"><h5><i class="fas fa-leaf me-2"></i>GaiaLumen</h5><p>Une communauté dédiée à l'écologie</p></div>
            <div class="col-md-4 mb-4"><h5>Contact</h5><p><i class="fas fa-envelope me-2"></i> contact@gaia.com</p><p><i class="fas fa-phone me-2"></i> +216 XX XXX XXX</p></div>
            <div class="col-md-4 mb-4"><h5>Suivez-nous</h5><div class="social-icons"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-twitter"></i></a></div></div>
        </div>
        <hr><p>&copy; 2026 GaiaLumen - Héritage de Gaia. Tous droits réservés.</p>
    </div>
</footer>
<script>
const themeToggle = document.getElementById('themeToggle');
const body = document.body;
const savedTheme = localStorage.getItem('theme');
if (savedTheme) { body.setAttribute('data-theme', savedTheme); updateButtonText(savedTheme); }
themeToggle.addEventListener('click', () => {
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateButtonText(newTheme);
});
function updateButtonText(theme) {
    themeToggle.innerHTML = theme === 'dark' ? '<i class="fas fa-sun me-1"></i> Mode clair' : '<i class="fas fa-moon me-1"></i> Mode sombre';
}
</script>
</body>
</html>