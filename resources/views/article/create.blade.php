<form action="{{route('article.store')}}" method="post">
    @csrf
    @method("POST")
    <input name="title">
    <textarea name="content"></textarea>
    <button type="submit"> OK </button>
</form>
