<div class="ui modal medium add">
    <div class="header">{{ __("Adicionar presença") }}</div>
    <div class="content">
        <form id="add_attendance_form" action="{{ url('attendance/add-entry') }}" class="ui form add-attendance" method="post" accept-charset="utf-8">
        @csrf
        <div class="field">
            <label>{{ __("Usuário") }}</label>
            <select class="ui search dropdown getref uppercase" name="name">
                <option value="">Selecione</option>
                @isset($employees)
                    @foreach ($employees as $data)
                    <option value="{{ $data->idno }}, {{ $data->firstname }}" data-ref="{{ $data->id }}">{{ $data->idno }}, {{ $data->firstname }}</option>
                    @endforeach
                @endisset
            </select>
        </div>
        <div class="field">
            <label for="">{{ __("Data") }}</label>
            <input type="date" name="date" data-position="top right">
        </div>
        <div class="field">
            <label for="">{{ __("Time IN") }} <span class="help">(Obrigatório)</span></label>
            <input class="time-mask" type="text" placeholder="00:00" name="timein" value="" required>
        </div>
        <div class="field">
            <label for="">{{ __("Time OUT") }} <span class="help">(Opcional)</span></label>
            <input class="time-mask" type="text" placeholder="00:00" name="timeout" value="">
        </div>
        <div class="field">
            <div class="ui error message">
                <i class="close icon"></i>
                <div class="header"></div>
                <ul class="list">
                    <li class=""></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="actions">
        <input type="hidden" value="" name="ref">
        <button class="ui positive approve small button" type="submit" name="submit"><i class="ui checkmark icon"></i> {{ __("Salvar") }}</button>
        <button class="ui grey cancel small button" type="button"><i class="ui times icon"></i> {{ __("Cancel") }}</button>
    </div>
    </form>
</div>

