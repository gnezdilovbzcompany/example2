<form action="{{route('article.update', $item)}}" method="post">
    @csrf
    @method("PUT")
    <input name="title" value="{{$item->title ?? ''}}">
    <textarea name="content">{{$item->content ?? ''}}</textarea>
    <button type="submit"> OK </button>
</form>
