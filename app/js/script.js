// ==========================================
// FICHIER : js/script.js
// Gère l'interactivité de la page réservation
// ==========================================

function mettreAJourHeuresPrecises() {
    const selectCreneau = document.getElementById('horaires_id');
    const selectHeure = document.getElementById('heure_reservation');
    
    // On récupère l'option actuellement sélectionnée dans la liste
    const optionSelectionnee = selectCreneau.options[selectCreneau.selectedIndex];

    // On vide la liste des heures
    selectHeure.innerHTML = '<option value="">-- Choisir une heure --</option>';

    // Si l'utilisateur remet sur la valeur par défaut
    if (!selectCreneau.value) {
        selectHeure.innerHTML = '<option value="">-- Sélectionnez d\'abord un créneau --</option>';
        return;
    }

    // On récupère les heures cachées dans le HTML par PHP
    const heuresDuCreneau = [
        optionSelectionnee.getAttribute('data-h0'),
        optionSelectionnee.getAttribute('data-h1'),
        optionSelectionnee.getAttribute('data-h2'),
        optionSelectionnee.getAttribute('data-h3')
    ];

    // On crée les nouvelles options pour chaque heure qui existe dans la BDD
    heuresDuCreneau.forEach(function(heure) {
        // Si la BDD contient une heure (et n'est pas NULL ni vide)
        if (heure && heure !== 'null' && heure.trim() !== '') {
            // On enlève les secondes pour l'affichage (ex: 08:30:00 -> 08:30)
            let heureAffichee = heure.substring(0, 5); 
            
            let option = document.createElement('option');
            option.value = heure; // Format BDD complet (HH:MM:SS) envoyé lors de la réservation
            option.text = heureAffichee;
            selectHeure.appendChild(option);
        }
    });
}