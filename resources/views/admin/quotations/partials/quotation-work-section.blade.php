@php
    $section = $section ?? [];
    $tasks = $section['tasks'] ?? [];

    if ($tasks === []) {
        $tasks = [[
            'name' => '',
            'description' => '',
            'duration_days' => '',
        ]];
    }
@endphp

<article class="quote-section-card" data-work-section data-section-index="{{ $sectionIndex }}" data-next-task-index="{{ count($tasks) }}">
    <div class="quote-section-card__header">
        <label class="form-field">
            <span>Bloque del plan de trabajo</span>
            <input type="text" name="work_sections[{{ $sectionIndex }}][title]" value="{{ $section['title'] ?? '' }}" placeholder="Ejemplo: Modulo Cotizaciones">

            @if ($errors->has("work_sections.$sectionIndex.title"))
                <small class="field-error">{{ $errors->first("work_sections.$sectionIndex.title") }}</small>
            @endif
        </label>

        <button class="button-link button-link--ghost button-link--compact" type="button" data-remove-work-section>
            Quitar bloque
        </button>
    </div>

    <div class="quote-task-list" data-work-task-list>
        @foreach ($tasks as $taskIndex => $task)
            @include('admin.quotations.partials.quotation-work-task', [
                'sectionIndex' => $sectionIndex,
                'taskIndex' => $taskIndex,
                'task' => $task,
            ])
        @endforeach
    </div>

    <div class="hero-actions">
        <button class="button-link button-link--ghost button-link--compact" type="button" data-add-work-task>
            Agregar tarea
        </button>
    </div>
</article>
