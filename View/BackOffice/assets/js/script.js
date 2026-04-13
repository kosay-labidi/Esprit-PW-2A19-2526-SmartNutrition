// Validation formulaire Ajout Événement (BackOffice) - AVEC CONTRÔLES AMÉLIORÉS
function validerEvenement() {
    event.preventDefault();

    let titre = document.getElementById("titre").value.trim();
    let date = document.getElementById("date").value;
    let heure = document.getElementById("heure").value;
    let type = document.getElementById("type").value;
    let description = document.getElementById("description") ? document.getElementById("description").value.trim() : "";

    // === CONTRÔLE DU TITRE ===
    if (titre.length === 0) {
        alert("❌ Le titre est obligatoire.");
        return false;
    }
    if (titre.length < 5) {
        alert("❌ Le titre doit contenir au moins 5 caractères.");
        return false;
    }
    if (titre.length > 100) {
        alert("❌ Le titre ne doit pas dépasser 100 caractères.");
        return false;
    }
    // Le titre ne doit pas contenir de caractères spéciaux dangereux
    let titreRegex = /^[a-zA-Z0-9À-ÿ\s\-',!?.:]+$/;
    if (!titreRegex.test(titre)) {
        alert("❌ Le titre contient des caractères non autorisés.");
        return false;
    }

    // === CONTRÔLE DE LA DESCRIPTION ===
    if (description.length > 500) {
        alert("❌ La description ne doit pas dépasser 500 caractères.");
        return false;
    }

    // === CONTRÔLE DE LA DATE ===
    if (!date) {
        alert("❌ La date est obligatoire.");
        return false;
    }
    
    // CONTRÔLE : Date pas dans le passé
    let dateObj = new Date(date);
    let today = new Date();
    today.setHours(0, 0, 0, 0);
    if (dateObj < today) {
        alert("❌ La date ne peut pas être dans le passé.");
        return false;
    }
    
    // CONTRÔLE : Date pas trop loin (max 2 ans)
    let maxDate = new Date();
    maxDate.setFullYear(maxDate.getFullYear() + 2);
    if (dateObj > maxDate) {
        alert("❌ La date ne peut pas être au-delà de 2 ans.");
        return false;
    }

    // === CONTRÔLE DE L'HEURE ===
    if (!heure) {
        alert("❌ L'heure est obligatoire.");
        return false;
    }
    let heureRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
    if (!heureRegex.test(heure)) {
        alert("❌ L'heure doit être au format HH:MM (ex: 14:30).");
        return false;
    }

    // === CONTRÔLE DU TYPE ===
    if (!type) {
        alert("❌ Veuillez choisir un type d'événement.");
        return false;
    }
    let typesValides = ["repas", "sport", "medical", "atelier"];
    if (!typesValides.includes(type)) {
        alert("❌ Veuillez choisir un type valide (repas, sport, medical, atelier).");
        return false;
    }

    // Si tous les contrôles sont passés
    alert("✅ Événement ajouté avec succès !");
    return true;
}

// Validation formulaire Participation (FrontOffice)
function validerParticipation() {
    let nom = document.getElementById("nom_complet").value.trim();
    let email = document.getElementById("email").value.trim();
    let telephone = document.getElementById("telephone").value.trim();

    // === CONTRÔLE DU NOM ===
    if (nom.length === 0) {
        alert("❌ Le nom complet est obligatoire.");
        return false;
    }
    if (nom.length < 3) {
        alert("❌ Le nom complet doit contenir au moins 3 caractères.");
        return false;
    }
    if (nom.length > 100) {
        alert("❌ Le nom complet ne doit pas dépasser 100 caractères.");
        return false;
    }

    // === CONTRÔLE DE L'EMAIL ===
    if (email.length === 0) {
        alert("❌ L'email est obligatoire.");
        return false;
    }
    if (!email.includes("@") || !email.includes(".")) {
        alert("❌ Veuillez entrer un email valide (exemple: nom@domaine.com).");
        return false;
    }

    // === CONTRÔLE DU TÉLÉPHONE ===
    if (telephone.length > 0) {
        let telephoneChiffres = telephone.replace(/\D/g, '');
        if (telephoneChiffres.length < 8) {
            alert("❌ Le numéro de téléphone doit contenir au moins 8 chiffres.");
            return false;
        }
        if (telephoneChiffres.length > 15) {
            alert("❌ Le numéro de téléphone ne doit pas dépasser 15 chiffres.");
            return false;
        }
    }

    // Si tous les contrôles sont passés
    alert("✅ Participation envoyée avec succès ! 🎉");
    return true;
}