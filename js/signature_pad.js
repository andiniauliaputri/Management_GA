/**
 * GA Management System - Canvas Digital Signature Pad Helper
 */

function initSignaturePad(canvasId, inputId, clearBtnId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const hiddenInput = document.getElementById(inputId);
    const clearBtn = document.getElementById(clearBtnId);

    let isDrawing = false;
    let hasDrawn = false;

    // Adjust canvas resolution for sharp display
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#1e293b';
    }

    // Call resize initial and on window resize
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX = e.clientX;
        let clientY = e.clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        hasDrawn = true;
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        if (e.cancelable) e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const pos = getPos(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        if (e.cancelable) e.preventDefault();
        saveSignature();
    }

    function stopDrawing() {
        if (isDrawing) {
            isDrawing = false;
            saveSignature();
        }
    }

    function saveSignature() {
        if (hiddenInput && hasDrawn) {
            hiddenInput.value = canvas.toDataURL('image/png');
        }
    }

    // Mouse Events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    // Touch Events for Mobile / Tablet / Portal Touchscreen
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);

    // Clear Button Event
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        });
    }

    // Bind form submit check
    const form = canvas.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (hasDrawn) {
                saveSignature();
            }
        });
    }
}

// Auto init signature pads on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('guest_signature_canvas')) {
        initSignaturePad('guest_signature_canvas', 'guest_signature_input', 'clear_guest_signature');
    }
    if (document.getElementById('borrow_signature_canvas')) {
        initSignaturePad('borrow_signature_canvas', 'borrow_signature_input', 'clear_borrow_signature');
    }
    if (document.getElementById('modal_guest_signature_canvas')) {
        initSignaturePad('modal_guest_signature_canvas', 'modal_guest_signature_input', 'clear_modal_guest_signature');
    }
    if (document.getElementById('modal_borrow_signature_canvas')) {
        initSignaturePad('modal_borrow_signature_canvas', 'modal_borrow_signature_input', 'clear_modal_borrow_signature');
    }
});
