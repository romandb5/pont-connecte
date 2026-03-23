// ==========================================
// FICHIER : js/script.js
// Gère l'interactivité de la page réservation
// ==========================================

function mettreAJourHeures() {
    const selectPeriode = document.getElementById('periode_id');
    const selectHeure = document.getElementById('heure_reservation');
    const valeurPeriode = selectPeriode.value;

    // On vide les heures précédentes
    selectHeure.innerHTML = '<option value="">-- Choisir une heure --</option>';

    if (!valeurPeriode) {
        selectHeure.innerHTML = '<option value="">-- Sélectionnez d\'abord une période --</option>';
        return;
    }

    let heuresDisponibles = [];

    // --- LOGIQUE DES HORAIRES ---
    
    if (valeurPeriode === '1') {
        // MATIN : de 06h00 à 12h00
        for (let h = 6; h <= 12; h++) {
            let heureFormattee = h.toString().padStart(2, '0');
            if (h === 12) {
                heuresDisponibles.push(`${heureFormattee}:00`); // S'arrête à 12:00 pile
            } else {
                heuresDisponibles.push(`${heureFormattee}:00`);
                heuresDisponibles.push(`${heureFormattee}:30`);
            }
        }
    } 
    else if (valeurPeriode === '2') {
        // APRÈS-MIDI : de 12h30 à 18h00
        for (let h = 12; h <= 18; h++) {
            let heureFormattee = h.toString().padStart(2, '0');
            if (h === 12) {
                heuresDisponibles.push(`${heureFormattee}:30`); // Commence à 12:30
            } else if (h === 18) {
                heuresDisponibles.push(`${heureFormattee}:00`); // S'arrête à 18:00 pile
            } else {
                heuresDisponibles.push(`${heureFormattee}:00`);
                heuresDisponibles.push(`${heureFormattee}:30`);
            }
        }
    } 
    else if (valeurPeriode === '3') {
        // SOIR : de 18h30 à 23h30
        for (let h = 18; h <= 23; h++) {
            let heureFormattee = h.toString().padStart(2, '0');
            if (h === 18) {
                heuresDisponibles.push(`${heureFormattee}:30`); // Commence à 18:30
            } else {
                heuresDisponibles.push(`${heureFormattee}:00`);
                heuresDisponibles.push(`${heureFormattee}:30`);
            }
        }
    }
    else if (valeurPeriode === '4') {
        // NUIT : de 00h30 à 05h30
        for (let h = 0; h <= 5; h++) {
            let heureFormattee = h.toString().padStart(2, '0');
            if (h === 0) {
                heuresDisponibles.push(`${heureFormattee}:30`); // Commence à 00:30
            } else {
                heuresDisponibles.push(`${heureFormattee}:00`);
                heuresDisponibles.push(`${heureFormattee}:30`);
            }
        }
    }

    // --- INJECTION HTML ---
    heuresDisponibles.forEach(function(heureStr) {
        let option = document.createElement('option');
        option.value = heureStr + ':00'; // Format BDD : HH:MM:SS
        option.text = heureStr;
        selectHeure.appendChild(option);
    });
}