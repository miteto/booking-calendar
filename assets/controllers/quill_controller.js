import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {
    connect() {
        // Create a container for Quill
        const container = document.createElement('div');
        this.element.parentNode.insertBefore(container, this.element.nextSibling);

        // Hide the original textarea
        this.element.style.display = 'none';

        // Initialize Quill
        this.quill = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // Set initial content
        this.quill.root.innerHTML = this.element.value;

        // Sync content back to textarea on change
        this.quill.on('text-change', () => {
            this.element.value = this.quill.root.innerHTML;
        });
    }
}
