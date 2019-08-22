@foreach($files as $key => $file)
    @php($path .= $key.',')
    @php($random = $key. '-LIMAN*'. $path . '*LIMAN-')
    @if(is_array($file))
        @if(strpos($key,"="))
            { "li_attr": { "title" : "{{explode("=",$key)[1]}}" }, "text" : "{{explode("=",$key)[1]}}", "children" : [@include('folder',["files" => $file, "path" => $path])], "id" : "{{$random}}"},
        @else
            { "li_attr": { "title" : "{{$key}}" }, "text" : "{{$key}}", "children" : [@include('folder',["files" => $file, "path" => $path])],"id" : "{{$random}}"},
        @endif
        
    @else
        { "li_attr": { "title" : "{{$file}}" }, "text" : "{{$file}}" },
    @endif
@endforeach
