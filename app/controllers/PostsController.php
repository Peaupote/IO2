<?php

class PostsController extends BaseController {

    protected $models = ['Post','Reply'];

    public function home() {
        App::$response->view('posts.home');
    }

    public function index() {
        $posts = $this->post->order('id','DESC')->findAll();
        App::$response->view ('posts.index',['posts'=>$posts]);
    }

    public function show ($id) {
        $replies = $this->reply->selectFillable()
                                ->where('post_id',$id)
                                ->order('id','desc')
                                ->get();
        $post = $this->post->findFirst($id);
        if (App::$request->isJson()) App::$response->json($post);
        if (empty($post)) throw new NotFoundException("Aucun post ne correspond à l'ID $id");
        $this->reply->last = [];
        App::$response->view('posts.show', ['post'=>$post,'replies'=>$replies],['Form'=>[$this->reply]]);
    }

    public function create () {
        App::$response->view('posts.create', ['method'=>'put'], ['Form'=>[$this->post]]);
    }

    public function store () {
        $this->validate ($this->post, App::$request->post);
        App::$request->filterPost();
        App::$request->post['user_id'] = Session::Auth()->id;
        $this->post->insert(App::$request->post);
        App::$response->redirect('/posts/');
    }

    public function edit ($id) {
        if (empty($this->post->findFirst($id))) throw new NotFoundException("Aucun post ne correspond à l'ID $id");
        App::$response->view('posts.create', ['method'=>'post'], ['Form'=>[$this->post]]);
    }

    public function update ($id) {
        $this->validate ($this->post, App::$request->post);
        App::$request->filterPost();
        $this->post->where('id',$id)->update(App::$request->post);
        App::$response->redirect('/posts/');
    }

    public function destroy ($id) {
        $post = $this->post->findFirst($id);
        if (empty($post) || Session::Auth()->id != $post->user_id) {
            throw new ForbbidenException("Vous ne pouvez pas supprimer ce post");
        }
        $this->post->last = [];
        $this->post->delete($id);
        if (App::$request->isJson()) App::$response->json(['success' => true,'message'=>'Post bien supprimé']);
        App::$response->redirect('/posts');
    }

    public function search() {
        if (!isset(App::$request->get['q']) || empty(App::$request->get['q'])) $results = [];
        else $results = $this->post->where('title','like','%'.App::$request->get['q'].'%')->get();
        App::$response->view('posts.search',['results'=>$results]);
    }

}
