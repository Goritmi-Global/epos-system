<script setup>
import { ref, onMounted } from 'vue';
import { useSumUp } from '@/composables/useSumUp';

const { state, loading, getReaders, pairReader } = useSumUp();

const showPairingModal = ref(false);
const pairingCode = ref('');
const pairingStep = ref(1);

onMounted(() => {
    getReaders();
});

const openConnectionGuide = () => {
    showPairingModal.value = true;
    pairingStep.value = 1;
    pairingCode.value = '';
};

const handlePairReader = async () => {
    if (pairingCode.value.length !== 8) {
        alert('Please enter the 8-digit pairing code from your Solo device');
        return;
    }

    try {
        await pairReader(pairingCode.value.toUpperCase());
        alert('✅ Reader connected successfully!');
        showPairingModal.value = false;
        pairingCode.value = '';
    } catch (error) {
        alert('❌ Failed to connect: ' + error.message);
    }
};

const nextStep = () => {
    pairingStep.value++;
};

const prevStep = () => {
    pairingStep.value--;
};
</script>

<template>
    <div class="sumup-connection">
        <!-- Connection Status -->
        <div v-if="state.connected" class="connection-status connected">
            <i class="fas fa-check-circle"></i>
            <span>Card Reader Connected</span>
            <button class="btn btn-sm btn-link" @click="openConnectionGuide">
                Change Device
            </button>
        </div>

        <div v-else class="connection-status disconnected">
            <i class="fas fa-exclamation-circle"></i>
            <span >No Card Reader Connected</span>
        </div>

        <!-- Connect Device Button -->
        <button 
            class="btn btn-primary btn-connect"
            @click="openConnectionGuide"
            :disabled="loading"
        >
            <i class="fas fa-link"></i>
            {{ state.connected ? 'Change Card Reader' : 'Connect Card Reader' }}
        </button>

        <!-- Pairing Modal -->
        <transition name="modal">
            <div v-if="showPairingModal" class="modal-overlay" @click.self="showPairingModal = false">
                <div class="modal-content pairing-modal">
                    <button class="modal-close" @click="showPairingModal = false">
                        <i class="fas fa-times"></i>
                    </button>

                    <h2>Connect SumUp Solo Card Reader</h2>

                    <!-- Progress Steps -->
                    <div class="progress-steps">
                        <div class="step" :class="{ active: pairingStep >= 1, complete: pairingStep > 1 }">
                            <div class="step-number">1</div>
                            <div class="step-label">Prepare Device</div>
                        </div>
                        <div class="step-line" :class="{ active: pairingStep > 1 }"></div>
                        <div class="step" :class="{ active: pairingStep >= 2, complete: pairingStep > 2 }">
                            <div class="step-number">2</div>
                            <div class="step-label">Get Code</div>
                        </div>
                        <div class="step-line" :class="{ active: pairingStep > 2 }"></div>
                        <div class="step" :class="{ active: pairingStep >= 3 }">
                            <div class="step-number">3</div>
                            <div class="step-label">Connect</div>
                        </div>
                    </div>

                    <!-- Step 1: Prepare Device -->
                    <div v-if="pairingStep === 1" class="pairing-step">
                        <div class="device-illustration">
                            <i class="fas fa-tablet-alt fa-5x"></i>
                        </div>
                        
                        <h3>Prepare Your Solo Device</h3>
                        
                        <div class="instruction-list">
                            <div class="instruction-item">
                                <div class="instruction-icon">
                                    <i class="fas fa-power-off"></i>
                                </div>
                                <div class="instruction-text">
                                    <strong>Turn on</strong> your Solo card reader
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-icon">
                                    <i class="fas fa-wifi"></i>
                                </div>
                                <div class="instruction-text">
                                    <strong>Connect to Wi-Fi</strong><br>
                                    <small>Menu → Connections → Wi-Fi</small>
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="instruction-text">
                                    <strong>Log out</strong> if logged in<br>
                                    <small>Menu → API → Disconnect (if connected)</small>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-lg" @click="nextStep">
                            Next Step
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>

                    <!-- Step 2: Get Pairing Code -->
                    <div v-if="pairingStep === 2" class="pairing-step">
                        <div class="device-illustration">
                            <i class="fas fa-mobile-alt fa-5x"></i>
                        </div>
                        
                        <h3>Get Pairing Code from Device</h3>
                        
                        <div class="instruction-list">
                            <div class="instruction-item highlighted">
                                <div class="instruction-icon">
                                    <i class="fas fa-hand-pointer"></i>
                                </div>
                                <div class="instruction-text">
                                    <strong>On your Solo device:</strong>
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-number">1</div>
                                <div class="instruction-text">
                                    Swipe down to open menu
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-number">2</div>
                                <div class="instruction-text">
                                    Tap <strong>Connections</strong>
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-number">3</div>
                                <div class="instruction-text">
                                    Select <strong>API</strong>
                                </div>
                            </div>

                            <div class="instruction-item">
                                <div class="instruction-number">4</div>
                                <div class="instruction-text">
                                    Tap <strong>Connect</strong>
                                </div>
                            </div>

                            <div class="instruction-item success">
                                <div class="instruction-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="instruction-text">
                                    You'll see an <strong>8-digit code</strong> on screen
                                </div>
                            </div>
                        </div>

                        <div class="code-example">
                            <div class="example-label">Example code:</div>
                            <div class="example-code">918MPQHZ</div>
                        </div>

                        <div class="step-actions">
                            <button class="btn btn-secondary" @click="prevStep">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                            <button class="btn btn-primary btn-lg" @click="nextStep">
                                I Have the Code
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Enter Pairing Code -->
                    <div v-if="pairingStep === 3" class="pairing-step">
                        <div class="device-illustration">
                            <i class="fas fa-keyboard fa-5x"></i>
                        </div>
                        
                        <h3>Enter Pairing Code</h3>
                        
                        <p class="text-center text-muted">
                            Enter the 8-digit code shown on your Solo device
                        </p>

                        <div class="pairing-code-input">
                            <input 
                                v-model="pairingCode"
                                type="text"
                                maxlength="8"
                                placeholder="Enter code"
                                class="form-control form-control-lg text-center"
                                style="text-transform: uppercase; letter-spacing: 8px; font-size: 28px; font-weight: bold;"
                                @input="pairingCode = pairingCode.toUpperCase()"
                                autofocus
                            />
                            <small class="text-muted">
                                {{ pairingCode.length }}/8 characters
                            </small>
                        </div>

                        <div v-if="pairingCode.length === 8" class="ready-indicator">
                            <i class="fas fa-check-circle"></i>
                            Code entered - ready to connect!
                        </div>

                        <div class="step-actions">
                            <button class="btn btn-secondary" @click="prevStep">
                                <i class="fas fa-arrow-left"></i>
                                Back
                            </button>
                            <button 
                                class="btn btn-success btn-lg"
                                @click="handlePairReader"
                                :disabled="loading || pairingCode.length !== 8"
                            >
                                <i class="fas fa-link"></i>
                                {{ loading ? 'Connecting...' : 'Connect Device' }}
                            </button>
                        </div>

                        <div class="help-text">
                            <i class="fas fa-info-circle"></i>
                            The pairing code expires after 5 minutes. If it expires, start over from Step 2.
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.sumup-connection {
    padding: 20px;
}

.connection-status {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.connection-status.connected {
    background: #d4edda;
    border: 1px solid #28a745;
    color: #155724;
}

.connection-status.disconnected {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
}

.connection-status i {
    font-size: 24px;
}

.btn-connect {
    width: 100%;
    padding: 8px;
    font-size: 18px;
    font-weight: 600;
}

/* Modal Styles - Dark Mode */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.modal-content {
    background: #1e1e1e;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
}

.pairing-modal {
    padding: 40px;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    border: none;
    background: #2d2d2d;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    color: #ffffff;
}

.modal-close:hover {
    background: #3d3d3d;
}

.pairing-modal h2 {
    text-align: center;
    margin-bottom: 32px;
    color: #ffffff;
}

/* Progress Steps - Dark Mode */
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 40px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #2d2d2d;
    color: #888;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    transition: all 0.3s;
}

.step.active .step-number {
    background: #007bff;
    color: white;
    box-shadow: 0 0 20px rgba(0, 123, 255, 0.5);
}

.step.complete .step-number {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 12px;
    color: #888;
}

.step.active .step-label {
    color: #007bff;
    font-weight: 600;
}

.step-line {
    width: 60px;
    height: 2px;
    background: #2d2d2d;
    margin: 0 8px;
    margin-bottom: 24px;
    transition: all 0.3s;
}

.step-line.active {
    background: #28a745;
}

/* Pairing Step - Dark Mode */
.pairing-step {
    text-align: center;
}

.device-illustration {
    color: #007bff;
    margin-bottom: 24px;
}

.pairing-step h3 {
    margin-bottom: 24px;
    color: #ffffff;
}

.text-muted {
    color: #aaa !important;
}

.text-center {
    text-align: center;
}

/* Instruction List - Dark Mode */
.instruction-list {
    text-align: left;
    margin: 32px 0;
}

.instruction-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #2d2d2d;
    border-radius: 8px;
    margin-bottom: 12px;
    color: #ffffff;
}

.instruction-item.highlighted {
    background: #1a3a52;
    border: 2px solid #007bff;
}

.instruction-item.success {
    background: #1a3d2e;
    border: 2px solid #28a745;
}

.instruction-icon {
    width: 48px;
    height: 48px;
    background: #1e1e1e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #007bff;
    flex-shrink: 0;
}

.instruction-number {
    width: 32px;
    height: 32px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.instruction-text {
    flex: 1;
    font-size: 16px;
    color: #ffffff;
}

.instruction-text small {
    color: #aaa;
    font-size: 14px;
}

/* Code Example - Dark Mode */
.code-example {
    background: #2d2d2d;
    border: 2px dashed #007bff;
    border-radius: 12px;
    padding: 24px;
    margin: 24px 0;
}

.example-label {
    font-size: 14px;
    color: #aaa;
    margin-bottom: 8px;
}

.example-code {
    font-size: 32px;
    font-weight: bold;
    letter-spacing: 8px;
    color: #007bff;
    font-family: 'Courier New', monospace;
}

/* Pairing Code Input - Dark Mode */
.pairing-code-input {
    margin: 32px 0;
}

.pairing-code-input .form-control {
    background: #2d2d2d;
    border: 2px solid #3d3d3d;
    color: #ffffff;
}

.pairing-code-input .form-control:focus {
    background: #2d2d2d;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    color: #ffffff;
}

.pairing-code-input small {
    color: #aaa;
}

.ready-indicator {
    background: #1a3d2e;
    border: 2px solid #28a745;
    color: #4ade80;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    font-weight: 600;
}

.ready-indicator i {
    font-size: 24px;
}

/* Step Actions */
.step-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 32px;
}

.step-actions .btn {
    min-width: 120px;
}

.help-text {
    margin-top: 24px;
    padding: 16px;
    background: #3d3200;
    border-radius: 8px;
    color: #ffc107;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #665200;
}

/* Button Overrides for Dark Mode */
.btn-primary {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #0056b3;
    border-color: #0056b3;
}

.btn-secondary {
    background: #3d3d3d;
    border-color: #3d3d3d;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #4d4d4d;
    border-color: #4d4d4d;
}

.btn-success {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #218838;
    border-color: #218838;
}

.btn-lg {
    padding: 12px 32px;
    font-size: 16px;
}

/* Animations */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: transform 0.3s;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: scale(0.9);
}
</style>