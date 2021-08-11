<link href="/js/task-component.js" rel=preload as=script>
<noscript>
    <strong>We're sorry but this extension doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
</noscript>
<div id="task" tasks='{{ json_encode($tasks) }}' @isset($onSuccess) onSuccess='{{ $onSuccess }}' @endisset @isset($onFail) onFail='{{ $onFail }}' @endisset></div>
<script src="/js/task-component.js"></script>