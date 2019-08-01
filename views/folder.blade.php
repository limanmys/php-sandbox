@foreach($files as $key => $file)
    @php($path .= $key.',')
    @php($random = $key. '-LIMAN*'. $path . '*LIMAN-')
    @if(is_array($file))
        @if(strpos($key,"="))
            { "text" : "{{explode("=",$key)[1]}}", "children" : [@include('folder',["files" => $file, "path" => $path])], "id" : "{{$random}}"},
        @else
            { "text" : "{{$key}}", "children" : [@include('folder',["files" => $file, "path" => $path])],"id" : "{{$random}}"},
        @endif
        
    @else
        { "text" : "{{$file}}" },
    @endif
@endforeach
