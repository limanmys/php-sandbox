@php($random = (isset($id)? $id : "a" . str_random(20)))
<input class="form-control" type="search" onchange="search{{$random}}(this)"/><br>

<div class="row">
    <div class="col-md-1">
    <button class="btn btn-warning" onclick="toogle{{$random}}()"><i id="icon{{$random}}" class="fa fa-caret-down"></i></button><br>

    </div>
    <div class="col-md-10">
    <div id="{{$random}}" style="overflow-x: auto; max-height: 450px; overflow-y: auto;"></div>

    </div>
</div>
<br>
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
                {{$click}}(getPath{{$random}}());
        @endisset
    });

    function getPath{{$random}}() {
        let path = $('#{{$random}}').jstree().get_path($('#{{$random}}').jstree("get_selected")[0], ',',true);
        path = path.replace(/-LIMAN\*.*?\*LIMAN-/g,'');
        @isset($ldapStyle)
            return path.split(",").reverse().join(',');
        @else
            return path;
        @endisset
    }
    function search{{$random}}(el) {
        $('#{{$random}}').jstree(true).search($(el).val());
    }
    let open{{$random}} = false;
    function toogle{{$random}}(){
        if(open{{$random}}){
            $('#{{$random}}').jstree('open_all');
        }else{
            $('#{{$random}}').jstree('close_all');
        }
        open{{$random}} = !open{{$random}};
        $("#icon{{$random}}").toggleClass('fa-caret-down').toggleClass('fa-caret-up');
    }

    @isset($menu)
    function {{$random}}customMenu() {
        return {
            @foreach($menu as $key=>$item)
            '{{random_int(1,100)}}': {
                label: "{{__($key)}}",
                action: {{$item}}
            },
            @endforeach
        };
    }
    @endisset
</script>