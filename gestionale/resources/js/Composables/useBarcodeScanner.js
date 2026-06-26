import { ref, onUnmounted } from 'vue'
import { BrowserMultiFormatReader, NotFoundException } from '@zxing/browser'

/**
 * Composable per lettore barcode USB (emula tastiera) e scanner fotocamera.
 * Il lettore USB non richiede nessuna libreria: emette keystrokes terminati da Enter.
 */
export function useBarcodeScanner({ onScan, prefix = '', minLength = 3 } = {}) {
    // --- USB/HID scanner (emulazione tastiera) ---
    const usbBuffer = ref('')
    const usbLastKey = ref(0)
    const USB_TIMEOUT = 80 // ms: i lettori USB inviano tutti i caratteri < 80ms l'uno dall'altro

    let usbTimer = null

    function handleKeydown(e) {
        const now = Date.now()
        const delta = now - usbLastKey.value
        usbLastKey.value = now

        // Reset buffer se passa troppo tempo tra i tasti (digitazione umana)
        if (delta > USB_TIMEOUT && usbBuffer.value.length > 0) {
            usbBuffer.value = ''
        }

        if (e.key === 'Enter') {
            const code = usbBuffer.value.trim()
            usbBuffer.value = ''
            if (code.length >= minLength) {
                onScan?.(code, 'usb')
            }
            return
        }

        if (e.key.length === 1) {
            usbBuffer.value += e.key
        }
    }

    function attachUsbListener(el = document) {
        el.addEventListener('keydown', handleKeydown)
    }

    function detachUsbListener(el = document) {
        el.removeEventListener('keydown', handleKeydown)
    }

    // --- Camera scanner ---
    const cameraActive = ref(false)
    const cameraError = ref(null)
    let codeReader = null

    async function startCamera(videoEl) {
        try {
            codeReader = new BrowserMultiFormatReader()
            cameraActive.value = true
            cameraError.value = null

            await codeReader.decodeFromVideoDevice(undefined, videoEl, (result, err) => {
                if (result) {
                    onScan?.(result.getText(), 'camera')
                }
                if (err && !(err instanceof NotFoundException)) {
                    console.warn('Scanner error:', err)
                }
            })
        } catch (e) {
            cameraError.value = e.message
            cameraActive.value = false
        }
    }

    function stopCamera() {
        codeReader?.reset()
        cameraActive.value = false
    }

    onUnmounted(() => {
        detachUsbListener()
        stopCamera()
    })

    return {
        // USB
        attachUsbListener,
        detachUsbListener,
        usbBuffer,
        // Camera
        cameraActive,
        cameraError,
        startCamera,
        stopCamera,
    }
}
