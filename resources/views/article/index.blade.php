@extends('layouts.app')

@... Title

@section('content')

<ul>
    @foreach($list as $item)
        <li> {{$item->title2}} / {!!  $item->content !!}  <a class=" " href="{{route('article.edit', $item)}}"> EDIT </a> </li>
    @endforeach
</ul>



    @endsection


@push('js_footer')
    <script src="">
        //....
    </script>
@endpush
