@extends('layouts.app')

@section('title', 'Notes')
@section('page-title', 'Notes')
@section('page-subtitle', 'Keep track of your campaign notes')

@section('content')
    <style>
        .notes-page { display: grid; grid-template-columns: 380px 1fr; gap: 24px; }
        @media(max-width: 900px) { .notes-page { grid-template-columns: 1fr; } }

        .note-composer {
            background: linear-gradient(145deg, #1e1e2e, #1a1a2a);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.06);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        .note-composer h3 { margin: 0 0 16px; color: #e2e8f0; font-size: 16px; font-weight: 600; }
        .note-composer textarea {
            width: 100%; background: #13131f; color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;
            padding: 14px; font-size: 14px; resize: vertical; font-family: inherit;
            min-height: 120px; transition: border-color 0.2s;
        }
        .note-composer textarea:focus { border-color: #818cf8; outline: none; }
        .note-composer textarea::placeholder { color: #4a5568; }
        .composer-controls { display: flex; flex-direction: column; gap: 10px; margin-top: 14px; }
        .composer-row { display: flex; gap: 8px; }
        .composer-select {
            flex: 1; background: #13131f; color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;
            padding: 8px 12px; font-size: 13px; cursor: pointer;
        }
        .save-btn {
            background: linear-gradient(135deg, #818cf8, #6366f1); color: #fff;
            border: none; padding: 10px 24px; border-radius: 10px; cursor: pointer;
            font-size: 14px; font-weight: 600; transition: all 0.2s;
            width: 100%;
        }
        .save-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(99,102,241,0.4); }
        .save-btn:active { transform: translateY(0); }

        .notes-feed { display: flex; flex-direction: column; gap: 12px; }
        .notes-feed-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .notes-feed-header h3 { margin: 0; color: #e2e8f0; font-size: 16px; font-weight: 600; }
        .note-count { background: #818cf8; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 20px; font-weight: 600; }

        .note-card {
            background: #1e1e2e; border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px; padding: 18px; transition: all 0.2s;
            position: relative;
        }
        .note-card:hover { border-color: rgba(129,140,248,0.3); transform: translateY(-1px); }
        .note-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .note-type-badge {
            font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .note-type-badge.general { background: rgba(99,102,241,0.15); color: #818cf8; }
        .note-type-badge.client { background: rgba(16,185,129,0.15); color: #10b981; }
        .note-type-badge.campaign { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .note-time { color: #4a5568; font-size: 12px; }
        .note-content { color: #cbd5e1; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
        .note-delete {
            position: absolute; top: 14px; right: 14px;
            background: none; border: none; color: #4a5568; cursor: pointer;
            padding: 4px 8px; border-radius: 6px; font-size: 14px; transition: all 0.2s;
        }
        .note-delete:hover { background: rgba(239,68,68,0.15); color: #ef4444; }

        .empty-notes {
            text-align: center; padding: 60px 20px; color: #4a5568;
            background: #1e1e2e; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.08);
        }
        .empty-notes .empty-icon { font-size: 48px; margin-bottom: 12px; }
        .empty-notes p { margin: 0; font-size: 14px; }
    </style>

    <div class="notes-page">
        <div class="note-composer">
            <h3>✏️ New Note</h3>
            <textarea id="noteContent" placeholder="Write your note here..."></textarea>
            <div class="composer-controls">
                <div class="composer-row">
                    <select id="noteType" class="composer-select">
                        <option value="general">📝 General</option>
                        <option value="client">👤 Client</option>
                        <option value="campaign">📊 Campaign</option>
                    </select>
                    <select id="noteClient" class="composer-select" style="display:none;">
                        <option value="">Select client...</option>
                    </select>
                </div>
                <button onclick="saveNote()" class="save-btn">Save Note</button>
            </div>
        </div>

        <div>
            <div class="notes-feed-header">
                <h3>📋 All Notes</h3>
                <span class="note-count" id="noteCount">0</span>
            </div>
            <div class="notes-feed" id="notesList">
                <div class="empty-notes">
                    <div class="empty-icon">📝</div>
                    <p>Loading notes...</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const csrfToken = '{{ csrf_token() }}';

            document.getElementById('noteType').addEventListener('change', function() {
                document.getElementById('noteClient').style.display = (this.value !== 'general') ? '' : 'none';
            });

            function loadClients() {
                fetch('/api/overview')
                    .then(r => r.json())
                    .then(data => {
                        const select = document.getElementById('noteClient');
                        select.innerHTML = '<option value="">Select client...</option>';
                        (data.clients || []).forEach(c => {
                            select.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                        });
                    });
            }

            function loadNotes() {
                fetch('/api/notes')
                    .then(r => r.json())
                    .then(notes => {
                        const list = document.getElementById('notesList');
                        document.getElementById('noteCount').textContent = notes.length;

                        if (!notes.length) {
                            list.innerHTML = `<div class="empty-notes"><div class="empty-icon">📝</div><p>No notes yet. Create one to get started!</p></div>`;
                            return;
                        }

                        list.innerHTML = '';
                        notes.forEach(note => {
                            const date = new Date(note.created_at);
                            const timeStr = date.toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric'}) + ' at ' + date.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
                            const typeLabel = {general:'General', client:'Client', campaign:'Campaign'}[note.type] || 'General';

                            list.innerHTML += `
                            <div class="note-card">
                                <button class="note-delete" onclick="deleteNote(${note.id})" title="Delete note">🗑</button>
                                <div class="note-meta">
                                    <span class="note-type-badge ${note.type}">${typeLabel}</span>
                                    <span class="note-time">${timeStr}</span>
                                </div>
                                <div class="note-content">${escapeHtml(note.content)}</div>
                            </div>`;
                        });
                    });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function saveNote() {
                const content = document.getElementById('noteContent').value.trim();
                if (!content) return;

                const btn = document.querySelector('.save-btn');
                btn.textContent = 'Saving...';
                btn.disabled = true;

                fetch('/api/notes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        content: content,
                        type: document.getElementById('noteType').value,
                        client_id: document.getElementById('noteClient').value || null,
                    })
                })
                .then(r => r.json())
                .then(() => {
                    document.getElementById('noteContent').value = '';
                    btn.textContent = 'Save Note';
                    btn.disabled = false;
                    loadNotes();
                })
                .catch(() => {
                    btn.textContent = 'Save Note';
                    btn.disabled = false;
                });
            }

            function deleteNote(id) {
                if (!confirm('Delete this note?')) return;
                fetch(`/api/notes/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                })
                .then(() => loadNotes());
            }

            document.addEventListener('DOMContentLoaded', () => {
                loadClients();
                loadNotes();
            });

            function refreshData() { loadNotes(); }
        </script>
    @endpush
@endsection