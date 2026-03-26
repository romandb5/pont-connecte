// ==========================================
// FICHIER : js/script.js
// Gère l'interactivité de la page réservation
// ==========================================

function mettreAJourHeuresPrecises() {
    const selectCreneau = document.getElementById('bloc_creneau');
    const selectHeure = document.getElementById('heure_reservation');
    
    // On vide la liste des heures
    selectHeure.innerHTML = '<option value="">-- Choisir une heure --</option>';

    if (!selectCreneau.value) {
        selectHeure.innerHTML = '<option value="">-- Sélectionnez d\'abord une période --</option>';
        return;
    }

    const optionSelectionnee = selectCreneau.options[selectCreneau.selectedIndex];
    
    // On récupère le tableau JSON caché généré par PHP
    const heuresJson = optionSelectionnee.getAttribute('data-heures');

    if (heuresJson) {
        // On transforme le texte JSON en vrai tableau JavaScript
        const heuresTableau = JSON.parse(heuresJson);

        heuresTableau.forEach(function(combo) {
            // "combo" ressemble à "12|08:30:00" (HORAIRES_ID | HEURE_PRECISE)
            let parts = combo.split('|');
            let heure = parts[1]; // On ne prend que la partie "08:30:00" pour l'affichage
            
            // On enlève les secondes pour que ce soit plus joli (ex: 08:30)
            let heureAffichee = heure.substring(0, 5); 
            
            let option = document.createElement('option');
            option.value = combo; // C'est le combo entier qui sera envoyé au PHP !
            option.text = heureAffichee;
            selectHeure.appendChild(option);
        });
    }
}