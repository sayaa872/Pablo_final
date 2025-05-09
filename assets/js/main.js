// Script principal pour le site commémoratif de Pablo

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des éléments interactifs
    initGallery();
    initAlbumForms();
    initPhotoUpload();
    initDeleteConfirmation();
});

// Fonction pour initialiser la galerie de photos
function initGallery() {
    // Ajouter des écouteurs d'événements pour les images de la galerie
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-btn') || 
                e.target.classList.contains('edit-btn') ||
                e.target.classList.contains('album-add-btn')) {
                return; // Ne pas ouvrir l'image si on clique sur un bouton
            }
            
            const imgSrc = this.querySelector('img').src;
            const imgTitle = this.querySelector('.gallery-caption h4')?.textContent || '';
            const imgDesc = this.querySelector('.gallery-caption p')?.textContent || '';
            
            openLightbox(imgSrc, imgTitle, imgDesc);
        });
    });
}

// Fonction pour ouvrir une image en mode lightbox
function openLightbox(src, title, description) {
    // Créer l'élément lightbox
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="close-lightbox">&times;</span>
            <img src="${src}" alt="${title}">
            <div class="lightbox-caption">
                <h3>${title}</h3>
                <p>${description}</p>
            </div>
        </div>
    `;
    
    // Ajouter au body
    document.body.appendChild(lightbox);
    document.body.style.overflow = 'hidden'; // Empêcher le défilement
    
    // Fermer le lightbox au clic
    lightbox.querySelector('.close-lightbox').addEventListener('click', function() {
        document.body.removeChild(lightbox);
        document.body.style.overflow = 'auto';
    });
    
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            document.body.removeChild(lightbox);
            document.body.style.overflow = 'auto';
        }
    });
}

// Fonction pour initialiser les formulaires d'album
function initAlbumForms() {
    // Gestion du formulaire de création d'album
    const albumForm = document.getElementById('album-form');
    if (albumForm) {
        albumForm.addEventListener('submit', function(e) {
            const albumName = document.getElementById('album-name');
            if (!albumName.value.trim()) {
                e.preventDefault();
                showMessage('Veuillez entrer un nom pour l\'album', 'error');
            }
        });
    }
    
    // Gestion de l'ajout de photos à un album
    const addToAlbumBtns = document.querySelectorAll('.album-add-btn');
    addToAlbumBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const photoId = this.dataset.photoId;
            const albumSelect = document.getElementById('album-select');
            
            if (albumSelect) {
                // Afficher le modal de sélection d'album
                const modal = document.getElementById('album-select-modal');
                modal.style.display = 'block';
                
                // Stocker l'ID de la photo dans un champ caché
                document.getElementById('selected-photo-id').value = photoId;
            }
        });
    });
}

// Fonction pour initialiser le téléchargement de photos
function initPhotoUpload() {
    const uploadForm = document.getElementById('upload-form');
    const fileInput = document.getElementById('photo-file');
    
    if (uploadForm && fileInput) {
        // Prévisualisation de l'image avant upload
        fileInput.addEventListener('change', function() {
            const preview = document.getElementById('photo-preview');
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Validation du formulaire
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files[0]) {
                e.preventDefault();
                showMessage('Veuillez sélectionner une photo à télécharger', 'error');
            }
        });
    }
}

// Fonction pour initialiser les confirmations de suppression
function initDeleteConfirmation() {
    const deleteBtns = document.querySelectorAll('.delete-btn');
    
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });
}

// Fonction pour afficher des messages à l'utilisateur
function showMessage(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Faire disparaître le message après 5 secondes
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => {
            alertDiv.remove();
        }, 500);
    }, 5000);
}

// Ajouter des styles CSS pour le lightbox
const style = document.createElement('style');
style.textContent = `
.lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.lightbox img {
    max-width: 100%;
    max-height: 80vh;
    display: block;
    border: 3px solid white;
}

.close-lightbox {
    position: absolute;
    top: -30px;
    right: 0;
    color: white;
    font-size: 30px;
    cursor: pointer;
}

.lightbox-caption {
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 10px;
    margin-top: -4px;
}

.lightbox-caption h3 {
    margin: 0 0 5px 0;
}

.lightbox-caption p {
    margin: 0;
}
`;

document.head.appendChild(style);