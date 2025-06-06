<?php
$seenIcon = !!$seen ? 'check-double' : 'check';
$timeAndSeen =
    "<span data-time='$created_at' class='message-time'>
        " .
    ($isSender ? "<span class='fas fa-$seenIcon' seen'></span>" : '') .
    " <span class='time'>$timeAgo</span>
    </span>";
$audioId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
$messenger_color = App\Models\User::find(1)->messenger_color;
$senderColor = $messenger_color ? $messenger_color : Chatify::getFallbackColor();
?>

<div class="message-card @if ($isSender) mc-sender @endif" data-id="{{ $id }}">
    {{-- Delete Message Button --}}
    @if ($isSender)
        <div class="actions">
            <i class="fas fa-trash delete-btn" data-id="{{ $id }}"></i>
        </div>
        <div class="actions">
            <i class="fas fa-edit edit-btn" data-id="{{ $id }}" data-message="{{$message}}"></i>
        </div>
    @endif
    {{-- Card --}}
    <div class="message-card-content">
        @if (!in_array(@$attachment->type, ['image', 'audio']) || $message)
            <div class="message">
                {!! $message == null && $attachment != null && @$attachment->type != 'file'
                    ? $attachment->title
                    : nl2br($message) !!}
                {!! $timeAndSeen !!}
                {{-- If attachment is a file --}}
                @if (@$attachment->type == 'file')
                    <a href="{{ route(config('chatify.attachments.download_route_name'), ['fileName' => $attachment->file]) }}"
                        class="file-download">
                        <span class="fas fa-file"></span> {{ $attachment->title }}</a>
                @endif
            </div>
        @endif
        @if (@$attachment->type == 'image')
            <div class="image-wrapper" style="text-align: {{ $isSender ? 'end' : 'start' }}">
                <div class="image-file chat-image"
                    style="background-image: url('{{ Chatify::getAttachmentUrl($attachment->file) }}')">
                    <div>{{ $attachment->title }}</div>
                </div>
                <div style="margin-bottom:5px">
                    {!! $timeAndSeen !!}
                </div>
            </div>
        @endif
        @if (@$attachment->type == 'audio')
            <div class="message">
                <div style="margin-bottom:5px">
                    {!! $timeAndSeen !!}
                </div>
                <div class="audio-container" style="display: flex;">
                    <i class="fas fa-play" style="color: {{ $isSender ? 'white' : $senderColor }}"
                        id="playBtn{{ $audioId }}" onclick="playAudio(this)" data-audio="{{ $audioId }}"
                        style="color: white;margin-right:5px;"></i>
                    <div class="waveform" id="waveform{{ $audioId }}" style="width: 100%; height: 40px;"></div>
                    <span class="audio-duration" id="audio-duration{{ $audioId }}" style="white-space: nowrap;"></span>
                </div>
            </div>
        @endif
        @if ($admin_name)
            <span style="font-size: 10px; margin-right: 10px;">sent by {{$admin_name}}</span>
        @endif
    </div>
</div>
@if ($attachment->type == 'audio')
    <script>
        if (!audioVar) {
            var audioVar = {}
            audioVar["{{ $audioId }}"] = WaveSurfer.create({
                container: '#waveform{{ $audioId }}',
                waveColor: "{{ $isSender ? '#ffffff' : $senderColor }}",
                progressColor: '#4da8f9',
                height: 40,
                cursorWidth: 0,
                barWidth: 4,
                barGap: 3,
                barRadius: 5,
                url: "{{ Chatify::getAttachmentUrl($attachment->file) }}"
            });
        } else {
            audioVar["{{ $audioId }}"] = WaveSurfer.create({
                container: '#waveform{{ $audioId }}',
                waveColor: "{{ $isSender ? '#ffffff' : $senderColor }}",
                progressColor: '#4da8f9',
                height: 40,
                cursorWidth: 0,
                barWidth: 4,
                barGap: 3,
                barRadius: 5,
                url: "{{ Chatify::getAttachmentUrl($attachment->file) }}"
            });
        }
        getDuration("{{ Chatify::getAttachmentUrl($attachment->file) }}", function(length) {
            document.querySelector("#audio-duration{{ $audioId }}").textContent = length;
        });

        function getDuration(src, cb) {
            var audio = new Audio();
            $(audio).on("loadedmetadata", function() {
                const duration = formatDuration(audio.duration);
                cb(duration);
            });
            audio.src = src;
        }

        function formatDuration(duration) {
            const minutes = Math.floor(duration / 60); // Get total minutes
            const seconds = Math.floor(duration % 60); // Get remaining seconds
            return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`; // Format to MM:SS
        }

        function playAudio(ele) {
            let vm = audioVar[ele.dataset.audio]
            if (ele.classList.contains('fa-play')) {
                ele.classList.remove('fa-play')
                ele.classList.add('fa-pause')

                vm.play();
                vm.media.ontimeupdate = () => {
                    document.querySelector(`#audio-duration${ele.dataset.audio}`).textContent = formatDuration(vm.media
                        .duration - vm.media.currentTime)
                    if (vm.media.currentTime == vm.media.duration) {
                        document.querySelector(`#audio-duration${ele.dataset.audio}`).textContent = formatDuration(vm
                            .media
                            .duration)
                    }
                }

                vm.on('finish', () => {
                    let btn = document.getElementById(`playBtn${ele.dataset.audio}`)

                    btn.classList.remove('fa-pause');
                    btn.classList.add('fa-play');
                })
            } else {
                ele.classList.add('fa-play')
                ele.classList.remove('fa-pause')

                vm.pause()
            }
        }
    </script>
@endif
