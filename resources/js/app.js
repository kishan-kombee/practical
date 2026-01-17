import './../../vendor/power-components/livewire-powergrid/dist/powergrid'
import flatpickr from "flatpickr";
// Include component assets here
import './dropzone.js';
// Import PowerGrid responsive functionality
import './powergrid-responsive.js';

import Quill from 'quill';
import './form-handlers';
import './product-validation';
import './quill-editor';
import Swal from 'sweetalert2';
import './export-stream';

window.Quill = Quill;
window.Swal = Swal;

initSlidePanel();

document.addEventListener('livewire:initialized', () => {

    initModelMethods();

    Livewire.on('autoFocusElement', function(params) {
        if (params.elId) {
            setAutoFocus(params.elId);
        }
    });

    Livewire.on('toast', (event) => {
        const { type, message, title, position: toastPosition, timer } = event[0];
        Swal.fire({
            toast: true,
            position: toastPosition || 'top-end',
            icon: type || 'info',
            title: title || '',
            text: message || '',
            showConfirmButton: false,
            timer: timer || 2500,
            timerProgressBar: true,
            background: '#fff',
            customClass: {
                popup: 'rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-800 dark:text-gray-200'
            },
        });
    });

    Livewire.on('updateExportProgress', (eventData) => {
        try {
            const eventObj = JSON.parse(eventData);
            const exportProgress = eventObj.exportProgress;
            const waitingMessage = eventObj.waitingMessage;
            document.getElementById('progressBarDiv').style.display = 'block';
            document.getElementById('progressBar').style.width = exportProgress + '%';
            document.getElementById('progressText').textContent = exportProgress + '%';
            document.getElementById('waitingMessage').textContent = waitingMessage;

            setTimeout(() => {
                Livewire.dispatch('showExportProgressEvent', eventData);
            }, 1000);

        } catch (err) {
            console.log(err.message);
        }
    });

    Livewire.on('stopExportProgressEvent', () => {
        try {
            stopExportProgress();

        } catch (err) {
            console.log(err.message);
        }
    });

    Livewire.on('downloadExportFileEvent', (downloadedEventData) => {
        try {
            downloadExportFile(downloadedEventData);

        } catch (err) {
            console.log(err.message);
        }
    });

});

function stopExportProgress() {
    try {
        document.getElementById('progressBarDiv').style.display = 'none';

    } catch (err) {
        console.log(err.message);
    }
}

function downloadExportFile(downloadedData) {
    try {
        const downloadedObj = JSON.parse(downloadedData);
        const downloadUrl = downloadedObj.downloadUrl;
        const downloadFileName = downloadedObj.downloadFileName;

        const downloadLink = document.createElement('a');
        downloadLink.href = downloadUrl;
        downloadLink.download = downloadFileName;
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

    } catch (err) {
        console.log(err.message);
    }
}

function setAutoFocus(elId) {
    setTimeout(() => {
        $(`#${elId}`).focus()
    }, 1500);
}

function initModelMethods() {
    // Listen for Livewire 'show-modal' and 'hide-modal' events
    window.addEventListener('show-modal', event => {
        const modal = document.querySelector(event.detail.id);
        if (!modal) return;

        // Show modal
        modal.classList.remove('hidden');    // make it visible
        document.body.classList.add('overflow-hidden'); // prevent scrolling
    });

    window.addEventListener('hide-modal', event => {
        const modal = document.querySelector(event.detail.id);
        if (!modal) return;

        // Hide modal
        modal.classList.add('hidden');       // hide
        document.body.classList.remove('overflow-hidden'); // restore scrolling
    });
}

function initSlidePanel() {
    const register = (AlpineInstance) => {
        AlpineInstance.data('slidePanel', () => ({
            open: false,
            title: '',
            component: '',
            params: {},
            show(title, component, params = {}) {
                this.title = title;
                this.component = component;
                this.params = params;
                this.open = true;
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('loadSlideComponent', {
                        component: this.component,
                        params: this.params
                    });
                }
            },
            hide() {
                this.open = false;
                this.title = '';
                this.component = '';
                this.params = {};
            }
        }));
    };

    if (window.Alpine) {
        // Alpine already initialized → register immediately
        register(window.Alpine);
    } else {
        // Register when Alpine initializes
        document.addEventListener('alpine:init', () => register(Alpine));
    }
}
