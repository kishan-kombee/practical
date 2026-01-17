// Store editor content before Livewire updates
function preserveEditorContent() {
    if (window.quillInstances) {
        Object.keys(window.quillInstances).forEach(editorId => {
            const quill = window.quillInstances[editorId];
            if (quill) {
                const content = quill.root.innerHTML;
                const editorElement = document.getElementById(editorId);
                if (editorElement) {
                    editorElement.setAttribute('data-preserved-content', content);
                }
            }
        });
    }
}

// Quill Editor Initialization
function initQuillEditors() {
    const editors = document.querySelectorAll('.quill-editor');

    editors.forEach(editorElement => {
        const editorId = editorElement.id;

        // Skip if already initialized (check both instance and data attribute)
        if (window.quillInstances && window.quillInstances[editorId] && 
            editorElement.getAttribute('data-quill-initialized') === 'true') {
            // Re-add emoji button if it's missing
            addEmojiButtonToExistingEditor(editorElement);
            return;
        }

        // Clean up existing instance if it exists
        if (window.quillInstances && window.quillInstances[editorId]) {
            try {
                window.quillInstances[editorId].destroy();
                delete window.quillInstances[editorId];
            } catch (e) {
                console.warn('Error destroying existing Quill instance:', e);
            }
        }

        const toolbar = editorElement.dataset.toolbar || 'basic';
        const placeholder = editorElement.dataset.placeholder || '';
        const wireModel = editorElement.dataset.wireModel;

        // Toolbar configurations
        const toolbarConfigs = {
            basic: [
                ['bold', 'italic', 'underline'],
                ['link'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ],
            minimal: [
                ['bold', 'italic'],
                ['clean']
            ],
            full: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'align': [] }],
                ['link', 'image', 'video'],
                ['formula', 'code'],
                ['clean']
            ]
        };


        const quillConfig = {
            theme: 'snow',
            placeholder: placeholder,
            modules: {
                toolbar: {
                    container: toolbarConfigs[toolbar] || toolbarConfigs.basic,
                    handlers: {
                        'image': function() {
                            this.selectLocalImage();
                        }
                    }
                }
            }
        };

        // Initialize Quill
        const quill = new Quill(editorElement, quillConfig);
        
        // Store instance globally
        if (!window.quillInstances) {
            window.quillInstances = {};
        }
        window.quillInstances[editorId] = quill;

        // Add a data attribute to mark as initialized
        editorElement.setAttribute('data-quill-initialized', 'true');

        // Restore preserved content if it exists
        const preservedContent = editorElement.getAttribute('data-preserved-content');
        if (preservedContent) {
            quill.root.innerHTML = preservedContent;
            editorElement.removeAttribute('data-preserved-content');
            
            // Update the hidden input with the restored content
            const hiddenInput = document.getElementById(wireModel + '_hidden');
            if (hiddenInput) {
                hiddenInput.value = preservedContent;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        // Function to add emoji button
        function addEmojiButton() {
            if (toolbar === 'full') {
                const toolbarElement = editorElement.previousElementSibling;
                if (toolbarElement && toolbarElement.classList.contains('ql-toolbar')) {
                    // Check if emoji button already exists
                    if (toolbarElement.querySelector('.ql-emoji')) {
                        return;
                    }
                    
                    const emojiButton = document.createElement('button');
                    emojiButton.type = 'button';
                    emojiButton.className = 'ql-emoji';
                    emojiButton.innerHTML = '😀';
                    emojiButton.title = 'Insert Emoji';
                    emojiButton.style.cssText = `
                        width: 28px;
                        height: 28px;
                        border: none;
                        background: none;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        border-radius: 3px;
                        margin: 0 1px;
                    `;
                    
                    emojiButton.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = '#e6e6e6';
                    });
                    
                    emojiButton.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = 'transparent';
                    });
                    
                    emojiButton.addEventListener('click', function() {
                        showEmojiPickerForInstance(quill);
                    });
                    
                    toolbarElement.appendChild(emojiButton);
                }
            }
        }

        // Try to add emoji button multiple times with different delays
        setTimeout(addEmojiButton, 100);
        setTimeout(addEmojiButton, 500);
        setTimeout(addEmojiButton, 1000);
        setTimeout(addEmojiButton, 2000);

        // Use MutationObserver to watch for toolbar changes
        const observer = new MutationObserver(() => {
            addEmojiButton();
        });

        // Start observing the toolbar
        const toolbarElement = editorElement.previousElementSibling;
        if (toolbarElement) {
            observer.observe(toolbarElement, {
                childList: true,
                subtree: true,
                attributes: true
            });
        }




        // Custom image handler
        quill.getModule('toolbar').addHandler('image', function() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = function() {
                const file = input.files[0];
                if (file) {
                    // Check file size (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Image size should be less than 5MB');
                        return;
                    }

                    // Check file type
                    if (!file.type.startsWith('image/')) {
                        alert('Please select a valid image file');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const range = quill.getSelection(true);
                        quill.insertEmbed(range.index, 'image', e.target.result);
                        quill.setSelection(range.index + 1);
                    };
                    reader.readAsDataURL(file);
                }
            };
        });


        // Sync with Livewire
        const hiddenInput = document.getElementById(wireModel + '_hidden');
        
        if (hiddenInput) {
            // Update hidden input when editor content changes
            quill.on('text-change', function() {
                const content = quill.root.innerHTML;
                hiddenInput.value = content;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            });

            // Set initial content if exists
            if (hiddenInput.value) {
                quill.root.innerHTML = hiddenInput.value;
            }

            // Listen for Livewire updates
            Livewire.on('quill-update-' + editorId, (content) => {
                if (content !== quill.root.innerHTML) {
                    quill.root.innerHTML = content || '';
                    hiddenInput.value = content || '';
                }
            });
        }
    });
}

// Function to add emoji button to existing editors
function addEmojiButtonToExistingEditor(editorElement) {
    const toolbar = editorElement.dataset.toolbar || 'basic';
    
    if (toolbar === 'full') {
        const toolbarElement = editorElement.previousElementSibling;
        if (toolbarElement && toolbarElement.classList.contains('ql-toolbar')) {
            // Check if emoji button already exists
            if (toolbarElement.querySelector('.ql-emoji')) {
                return;
            }
            
            const emojiButton = document.createElement('button');
            emojiButton.type = 'button';
            emojiButton.className = 'ql-emoji';
            emojiButton.innerHTML = '😀';
            emojiButton.title = 'Insert Emoji';
            emojiButton.style.cssText = `
                width: 28px;
                height: 28px;
                border: none;
                background: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                border-radius: 3px;
                margin: 0 1px;
            `;
            
            emojiButton.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#e6e6e6';
            });
            
            emojiButton.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
            });
            
            emojiButton.addEventListener('click', function() {
                const editorId = editorElement.id;
                const quillInstance = window.quillInstances && window.quillInstances[editorId];
                if (quillInstance) {
                    showEmojiPickerForInstance(quillInstance);
                }
            });
            
            toolbarElement.appendChild(emojiButton);
        }
    }
}

// Function to show emoji picker for specific Quill instance
function showEmojiPickerForInstance(quillInstance) {
    // Remove any existing emoji picker
    const existingModal = document.querySelector('.emoji-picker-modal');
    if (existingModal) {
        existingModal.remove();
        return;
    }

    if (!quillInstance) {
        console.error('Quill instance not found');
        return;
    }

    const emojiList = [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
        '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
        '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
        '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣',
        '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬',
        '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗',
        '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯',
        '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐',
        '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈',
        '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾',
        '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿',
        '😾', '👶', '🧒', '👦', '👧', '🧑', '👨', '👩', '🧓', '👴',
        '👵', '👱', '👨‍🦰', '👩‍🦰', '👨‍🦱', '👩‍🦱', '👨‍🦳', '👩‍🦳', '👨‍🦲', '👩‍🦲',
        '🤵', '👰', '🤰', '🤱', '👼', '🎅', '🤶', '🦸', '🦹', '🧙',
        '🧚', '🧛', '🧜', '🧝', '🧞', '🧟', '💆', '💇', '🚶', '🏃',
        '💃', '🕺', '👯', '🧖', '🧗', '🤺', '🏇', '⛷️', '🏂', '🏌️',
        '🏄', '🚣', '🏊', '⛹️', '🏋️', '🚴', '🚵', '🤸', '🤼', '🤽',
        '🤾', '🤹', '🧘', '🛀', '🛌', '👭', '👫', '👬', '💏', '💑',
        '👪', '🗣️', '👤', '👥', '🫂', '👋', '🤚', '🖐️', '✋', '🖖',
        '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆',
        '🖕', '👇', '☝️', '👍', '👎', '👊', '✊', '🤛', '🤜', '👏',
        '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾',
        '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀',
        '👁️', '👅', '👄', '💋', '🩸', '💎', '🦴', '👓', '🕶️', '🥽',
        '🥼', '🦺', '👔', '👕', '👖', '🧣', '🧤', '🧥', '🧦', '👗',
        '👘', '🥻', '🩱', '🩲', '🩳', '👙', '👚', '👛', '👜', '👝',
        '🛍️', '🎒', '👞', '👟', '🥾', '🥿', '👠', '👡', '🩰', '👢',
        '👑', '👒', '🎩', '🎓', '🧢', '⛑️', '📿', '💄', '💍', '💎'
    ];

    // Create emoji picker modal
    const modal = document.createElement('div');
    modal.className = 'emoji-picker-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const picker = document.createElement('div');
    picker.style.cssText = `
        background: white;
        border-radius: 8px;
        padding: 20px;
        max-width: 400px;
        max-height: 300px;
        overflow-y: auto;
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 5px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    `;

    emojiList.forEach(emoji => {
        const emojiBtn = document.createElement('button');
        emojiBtn.textContent = emoji;
        emojiBtn.style.cssText = `
            border: none;
            background: none;
            font-size: 20px;
            padding: 5px;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
        `;
        emojiBtn.onmouseover = () => emojiBtn.style.background = '#f0f0f0';
        emojiBtn.onmouseout = () => emojiBtn.style.background = 'none';
        emojiBtn.onclick = () => {
            const range = quillInstance.getSelection(true);
            quillInstance.insertText(range.index, emoji);
            quillInstance.setSelection(range.index + 1);
            document.body.removeChild(modal);
        };
        picker.appendChild(emojiBtn);
    });

    modal.appendChild(picker);
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };

    document.body.appendChild(modal);
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initQuillEditors);

// Preserve content before Livewire updates
document.addEventListener('livewire:before-update', preserveEditorContent);
document.addEventListener('livewire:before-navigate', preserveEditorContent);

// Re-initialize on Livewire updates with proper timing
document.addEventListener('livewire:navigated', () => {
    setTimeout(initQuillEditors, 100);
    setTimeout(initQuillEditors, 500);
});

document.addEventListener('livewire:updated', () => {
    setTimeout(initQuillEditors, 100);
    setTimeout(initQuillEditors, 500);
});

// Also listen for specific Livewire events
document.addEventListener('livewire:load', () => {
    setTimeout(initQuillEditors, 100);
});

// Listen for form updates specifically
if (typeof Livewire !== 'undefined') {
    Livewire.on('quill-reinit', () => {
        setTimeout(initQuillEditors, 100);
    });
}

// Global MutationObserver to watch for new editor elements
const globalObserver = new MutationObserver((mutations) => {
    let shouldReinit = false;
    
    mutations.forEach((mutation) => {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Check if the added node is a quill editor or contains one
                    if (node.classList && node.classList.contains('quill-editor')) {
                        shouldReinit = true;
                    } else if (node.querySelector && node.querySelector('.quill-editor')) {
                        shouldReinit = true;
                    }
                }
            });
        }
    });
    
    if (shouldReinit) {
        setTimeout(initQuillEditors, 100);
    }
});

// Start observing the document body for changes
document.addEventListener('DOMContentLoaded', () => {
    globalObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
});