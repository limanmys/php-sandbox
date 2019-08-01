@php($random = (isset($id)? $id : "a" . str_random(20)))
<input class="form-control" type="search" onchange="search{{$random}}()" id="q"/>
<br>
<div id="{{$random}}"></div>
<script>
    $('#{{$random}}').jstree({
        "plugins": [
            @isset($menu)
            "contextmenu",
            @endisset
            "search",
            "state",
            "wholerow"
        ],
        "core": {
            "data": [
                @include("folder",["files" => $data])
            ],
            "check_callback": true
        },
        @isset($menu)
        "contextmenu": {
            items: {{$random}}customMenu,
        }
        @endisset
    }).on('select_node.jstree', function (e, data) {
        @isset($click)
                {{$click}}(getPath());
        @endisset
    });

    function getPath() {
        let path = $('#{{$random}}').jstree().get_path($('#{{$random}}').jstree("get_selected")[0], ',',true);
        path = path.replace(/-LIMAN\*.*?\*LIMAN-/g,'');
        @isset($ldapStyle)
            return path.split(",").reverse().join(',');
        @else
            return path;
        @endisset
    }

    function search{{$random}}() {
        $('#{{$random}}').jstree(true).search($("#q").val());
    }

    @isset($menu)
    function {{$random}}customMenu() {
        return {
            @foreach($menu as $key=>$item)
            '{{random_int(1,100)}}': {
                label: "{{__($key)}}",
                action: {{$item}}
            }
            @endforeach
        };
    }
    @endisset
</script>