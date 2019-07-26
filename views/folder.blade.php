@foreach($files as $key => $file)
    @php($random = $key. '*LIMAN*'.\Illuminate\Support\Str::random(10) . '*LIMAN*')
    @if(is_array($file))
        @if(strpos($key,"="))
            { "text" : "{{explode("=",$key)[1]}}", "children" : [@include('folder',["files" => $file])], "id" : "{{$random}}"},
        @else
            { "text" : "{{$key}}", "children" : [@include('folder',["files" => $file])],"id" : "{{$random}}"},
        @endif
        
    @else
        { "text" : "{{$file}}" },
    @endif
@endforeach