<div>
    <div class="modal-backdrop {{ $show ? 'active' : '' }}" wire:click.self="close">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Nouveau rendez-vous</h3>
                <button class="modal-close" wire:click="close">✕</button>
            </div>
            <form wire:submit="save">

                <div class="form-field">
                    <label>Patiente</label>
                    <div class="combo" x-data="{ open:false }" @click.outside="open=false">
                        <div class="combo-trigger {{ ! $newPatientId ? 'placeholder' : '' }}" :class="{ open: open }"
                             @click="open = !open; if(open){ $nextTick(() => $refs.patientSearchInput.focus()) }">
                            <span>{{ $newPatientId ? $selectedPatientName : '— Choisir une patiente —' }}</span>
                            <span class="combo-arrow" :class="{ rot: open }">▾</span>
                        </div>
                        <div class="combo-panel" x-show="open" x-cloak style="display:none;">
                            <div class="combo-search">
                                <input type="text" x-ref="patientSearchInput" wire:model.live.debounce.150ms="patientSearch"
                                       placeholder="Rechercher une patiente…" autocomplete="off" @click.stop>
                            </div>
                            <div class="combo-list">
                                @forelse($filteredPatients as $p)
                                    <div class="combo-option" x-on:click="open=false" wire:click="selectPatient({{ $p->id }})">{{ $p->first_name }} {{ $p->last_name }}</div>
                                @empty
                                    <div class="combo-empty">Aucune patiente trouvée.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @error('newPatientId') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Médecin</label>
                    @if(auth()->user()->role === 'medecin')
                        <div class="combo-trigger disabled"><span>{{ $selectedDoctorName }}</span></div>
                    @else
                        <div class="combo" x-data="{ open:false }" @click.outside="open=false">
                            <div class="combo-trigger {{ ! $newDoctorId ? 'placeholder' : '' }}" :class="{ open: open }"
                                 @click="open = !open; if(open){ $nextTick(() => $refs.doctorSearchInput.focus()) }">
                                <span>{{ $newDoctorId ? $selectedDoctorName : '— Choisir un médecin —' }}</span>
                                <span class="combo-arrow" :class="{ rot: open }">▾</span>
                            </div>
                            <div class="combo-panel" x-show="open" x-cloak style="display:none;">
                                <div class="combo-search">
                                    <input type="text" x-ref="doctorSearchInput" wire:model.live.debounce.150ms="doctorSearch"
                                           placeholder="Rechercher un médecin…" autocomplete="off" @click.stop>
                                </div>
                                <div class="combo-list">
                                    @forelse($filteredDoctors as $d)
                                        <div class="combo-option" x-on:click="open=false" wire:click="selectDoctor({{ $d->id }})">{{ $d->user->name }}</div>
                                    @empty
                                        <div class="combo-empty">Aucun médecin trouvé.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                    @error('newDoctorId') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Date</label>
                        <input type="date" wire:model="newDate">
                        @error('newDate') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Heure</label>
                        <input type="time" wire:model="newTime">
                        @error('newTime') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-field">
                    <label>Type de consultation</label>
                    <div class="type-toggle">
                        <input type="radio" id="new-inperson" value="in_person" wire:model="newType">
                        <label for="new-inperson">En présentiel</label>
                        <input type="radio" id="new-tele" value="teleconsultation" wire:model="newType">
                        <label for="new-tele">🎥 Téléconsultation</label>
                    </div>
                </div>
                <div class="form-field">
                    <label>Motif</label>
                    <input type="text" wire:model="newReason" placeholder="Ex : consultation gynéco, suivi de grossesse...">
                    @error('newReason') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="close">Annuler</button>
                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Créer le rendez-vous</span>
                        <span wire:loading wire:target="save">Création…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
