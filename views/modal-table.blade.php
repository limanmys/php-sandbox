<div class="modal fade" id="@isset($id){{$id}}@endisset">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    @isset($title)
                        {{__($title)}}
                    @endisset
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="width:100%; height:auto;padding:10px;">
                @include('table',$table)
            </div>
            @isset($footer)
            <div class="modal-footer justify-content-right">
                <button class="btn {{$footer["class"]}}" onclick="{{$footer["onclick"]}}">{{$footer["text"]}}</button>
            </div>
            @endisset
        </div>
    </div>
</div>