@php
    $task = $task ?? [];
@endphp

<article class="quote-task-card" data-work-task>
    <div class="form-grid form-grid--quote-task">
        <label class="form-field">
            <span>Tarea</span>
            <input type="text" name="work_sections[{{ $sectionIndex }}][tasks][{{ $taskIndex }}][name]" value="{{ $task['name'] ?? '' }}" placeholder="Nombre de la tarea">

            @if ($errors->has("work_sections.$sectionIndex.tasks.$taskIndex.name"))
                <small class="field-error">{{ $errors->first("work_sections.$sectionIndex.tasks.$taskIndex.name") }}</small>
            @endif
        </label>

        <label class="form-field">
            <span>Duracion (dias)</span>
            <input type="number" name="work_sections[{{ $sectionIndex }}][tasks][{{ $taskIndex }}][duration_days]" value="{{ $task['duration_days'] ?? '' }}" min="0" step="0.01" placeholder="1.00">

            @if ($errors->has("work_sections.$sectionIndex.tasks.$taskIndex.duration_days"))
                <small class="field-error">{{ $errors->first("work_sections.$sectionIndex.tasks.$taskIndex.duration_days") }}</small>
            @endif
        </label>

        <div class="quote-task-card__actions">
            <button class="button-link button-link--ghost button-link--compact" type="button" data-remove-work-task>
                Quitar tarea
            </button>
        </div>
    </div>

    <label class="form-field">
        <span>Descripcion</span>
        <textarea name="work_sections[{{ $sectionIndex }}][tasks][{{ $taskIndex }}][description]" rows="3" placeholder="Descripcion operativa o alcance de la tarea">{{ $task['description'] ?? '' }}</textarea>

        @if ($errors->has("work_sections.$sectionIndex.tasks.$taskIndex.description"))
            <small class="field-error">{{ $errors->first("work_sections.$sectionIndex.tasks.$taskIndex.description") }}</small>
        @endif
    </label>
</article>
