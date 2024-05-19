@extends('layouts.clock')

@section('content')
<br><br><br><br>
    <div class="container-fluid">       
        <div class="fixedcenter">            
            <div class="clockwrapper"></div>
            <div class="clockwrapper">
                <div class="timeclock">                    
                    <span id="show_day" class="clock-text"></span>                    
                    <span id="show_time" class="clock-time"></span>                    
                    <span id="show_date" class="clock-day"></span>
                </div>
            </div>
            <div class="clockwrapper">
                <div class="userinput">                    
                    <form action="" method="get" accept-charset="utf-8" class="ui form">                        
                        @isset($cc)
                            @if($cc == "on")                                
                                <div class="inline field comment">
                                    <textarea name="comment" class="uppercase lightblue" rows="1" placeholder="{{ __("Enter comment") }}" value=""></textarea>
                                </div> 
                            @endif
                        @endisset
                        <div class="inline field">
                            <input @if($rfid == 'on') id="rfid" @endif class="enter_idno uppercase @if($rfid == 'on') mr-0 @endif" name="idno" value="" type="text" placeholder="{{ __("ENTER YOUR ID") }}" required autofocus>
                            @if($rfid !== "on")
                                <button id="btnclockin" type="button" class="ui positive large icon button">{{ __("Confirm") }}</button>
                            @endif                           
                            <input type="hidden" id="_url" value="{{url('/')}}">
                        </div>
                    </form>
                </div>
            </div>
            <div id="loading">Lendo ID...</div>
            <div class="message-after">
                <p> 
                    <span id="greetings">{{ __("Welcome!") }}</span> 
                    <span id="fullname"></span>
                </p>
                <p id="messagewrap">
                    <span id="type"></span>
                    <span id="message"></span> 
                    <span id="time"></span>
                </p>
            </div>
        </div>
    </div>
@endsection
   
@section('scripts')
<script type="text/javascript">
    var elTime = document.getElementById('show_time');
    var elDate = document.getElementById('show_date');
    var elDay = document.getElementById('show_day');

    var setTime = function() {
        var time = moment().tz(timezone);
        @if($tf == 1) 
            elTime.innerHTML= time.format("hh:mm:ss A");
        @else
            elTime.innerHTML= time.format("kk:mm:ss");
        @endif

        var meses = {
            'January': 'Janeiro',
            'February': 'Fevereiro',
            'March': 'Março',
            'April': 'Abril',
            'May': 'Maio',
            'June': 'Junho',
            'July': 'Julho',
            'August': 'Agosto',
            'September': 'Setembro',
            'October': 'Outubro',
            'November': 'Novembro',
            'December': 'Dezembro'
        };
        elDate.innerHTML = time.format('D [de] ') + meses[time.format('MMMM')] + time.format(' [de] YYYY');

        var diasDaSemana = {
            'Monday': 'Segunda-feira',
            'Tuesday': 'Terça-feira',
            'Wednesday': 'Quarta-feira',
            'Thursday': 'Quinta-feira',
            'Friday': 'Sexta-feira',
            'Saturday': 'Sábado',
            'Sunday': 'Domingo'
        };
        elDay.innerHTML = diasDaSemana[time.format('dddd')];
    }

    setTime();
    setInterval(setTime, 1000);

    $("#rfid").on("input", function() {
        var idno = $(this).val().toUpperCase();

        if (idno.length < 5) {
            $('#loading').show();
            return;
        } else {
            $('#loading').hide();
        }

        var url = $("#_url").val();
        var type = $('.btnclock.active').data("type");
        var comment = $('textarea[name="comment"]').val();

        setTimeout(() => {
            $(this).val("");
        }, 600);

        $.ajax({
            url: url + '/attendance/add',
            type: 'post',
            dataType: 'json',
            data: {idno: idno, type: type, clockin_comment: comment},
            headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },

            success: function(response) {
                if (response['error'] != null) {
                    $('.message-after').addClass('notok').hide();
                    $('#message').text(response['error']);
                    $('.message-after').show();
                    setTimeout(function () {
                        $('.message-after').fadeOut('slow');
                    }, 5000);
                } else {
                    $('.message-after').removeClass('notok').addClass('ok').show();
                    var action = response.type === 'timein' ? "Entrada registrada às " : "Saída registrada às ";
                    var messageContent = action + response.time + ".<br><strong>" + response.idno + ", " + response.employee + "</strong>";

                    $('#message').html(messageContent);
                    setTimeout(function () {
                        $('.message-after').fadeOut('slow');
                    }, 5000);
                }
            }
        });
    });

    function printContent(content) {
        var iframe = document.createElement('iframe');
        iframe.style.height = '0';
        iframe.style.width = '0';
        iframe.style.position = 'absolute';
        document.body.appendChild(iframe);
        var iframeDoc = iframe.contentWindow.document;
        iframeDoc.body.innerHTML = content;
        iframe.contentWindow.print();
        setTimeout(function () {
            document.body.removeChild(iframe);
        }, 1000);
    }
</script>
@endsection
