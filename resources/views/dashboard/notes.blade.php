@extends('layouts.app')

@section('title', 'Notes')
@section('page-title', 'Notes')
@section('page-subtitle', 'Your notes and reminders')

@section('content')
    <div class="notes-header-bar">
        <button class="btn-primary" id="addNoteBtn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Add Note
        </button>
    </div>

    <!-- Add Note Modal -->
    <div class="note-modal" id="noteModal">
        <div class="note-modal-overlay" id="noteModalOverlay"></div>
        <div class="note-modal-content">
            <h3>Add Note</h3>
            <div class="form-group">
                <label for="noteClient">Client</label>
                <select id="noteClient" class="date-select">
                    @foreach($clients as $client)
                        <option value="{{ $client['name'] }}">{{ $client['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="noteText">Note</label>
                <textarea id="noteText" rows="4" placeholder="Write your note..."></textarea>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" id="cancelNoteBtn">Cancel</button>
                <button class="btn-primary" id="saveNoteBtn">Save Note</button>
            </div>
        </div>
    </div>

    <div class="notes-list" id="notesList">
        @foreach($notes as $note)
            <div class="note-card">
                <div class="note-card-header">
                    <span class="note-client-tag">{{ $note['client'] }}</span>
                    <span class="note-date">{{ $note['date'] }} at {{ $note['time'] }}</span>
                </div>
                <p class="note-text">{{ $note['text'] }}</p>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('noteModal');
                const addBtn = document.getElementById('addNoteBtn');
                const cancelBtn = document.getElementById('cancelNoteBtn');
                const saveBtn = document.getElementById('saveNoteBtn');
                const overlay = document.getElementById('noteModalOverlay');

                function openModal() { modal.classList.add('active'); }
                function closeModal() { modal.classList.remove('active'); }

                addBtn?.addEventListener('click', openModal);
                cancelBtn?.addEventListener('click', closeModal);
                overlay?.addEventListener('click', closeModal);

                saveBtn?.addEventListener('click', function () {
                    const client = document.getElementById('noteClient').value;
                    const text = document.getElementById('noteText').value;
                    if (!text.trim()) return;

                    const notesList = document.getElementById('notesList');
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                    const timeStr = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

                    const noteHtml = `
                    <div class="note-card" style="animation: slideIn 0.3s ease">
                        <div class="note-card-header">
                            <span class="note-client-tag">${client}</span>
                            <span class="note-date">${dateStr} at ${timeStr}</span>
                        </div>
                        <p class="note-text">${text}</p>
                    </div>
                `;
                    notesList.insertAdjacentHTML('afterbegin', noteHtml);
                    document.getElementById('noteText').value = '';
                    closeModal();
                });
            });
        </script>
    @endpush
@endsection