<?php
  namespace App\Controller;

  use Cake\Network\Exception\NotFoundException;

  class PostsController extends AppController {
    public function initialize() {
      parent::initialize();
      $this->loadComponent('Flash');
    }

    public function index () {
      $posts = $this->Posts->find('all')
        ->where(['Posts.status =' => 'featured'])
        ->orWhere(['Posts.status =' => 'important'])
        ->order(['Posts.created' => 'DESC'])
        ->contain(['Users', 'Categories'])
        ->limit(5);

      $recents = $this->Posts->find('all')
        ->order(['Posts.created' => 'DESC'])
        ->contain(['Users', 'Categories'])
        ->limit(7);

      $title = 'Overview';
      $this->set(compact('posts','recents','title'));
    }

    public function view($id = null) {
      $post = $this->Posts->get($id);
      $author = $this->Posts->Users->get($post->user_id);
      $category = $this->Posts->Categories->get($post->category_id);

      $title = $post->title;
      $this->set(compact('post','category','author','title'));
    }

    public function add() {
      $post = $this->Posts->newEntity();
      if ($this->request->is('post')) {
        $post = $this->Posts->patchEntity($post, $this->request->data);
        $post->user_id = $this->Auth->user('id');
        if ($this->Posts->save($post)){
          $this->Flash->success(__('Your blog post is saved!'));
          return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('Error saving your blog post.'));
      }
      $this->set('post', $post);

      $categories = $this->Posts->Categories->find('treeList');
      $title = 'New blog post';
      $this->set(compact('categories','title'));
    }

    public function edit($id = null) {
      $post = $this->Posts->get($id);
      if ($this->request->is(['post', 'put'])) {
        $post = $this->Posts->patchEntity($post, $this->request->data);
        if ($this->Posts->save($post)) {
          $this->Flash->success(__('Your blog post has been updated!'));
          return $this->redirect(['action' => 'index']);
        }
        $this->Flash->error(__('Unable to update your blog post.'));
      }
      $this->set('post', $post);

      $categories = $this->Posts->Categories->find('treeList');
      $title = 'Edit: ' . $post->title;
      $this->set(compact('categories','title'));
    }

    public function delete($id) {
      $this->request->allowMethod(['post', 'delete']);
      $post = $this->Posts->get($id);
      if ($this->Posts->delete($post)) {
        $this->Flash->success(__('The blog post with id: {0} has been deleted.', h($id)));
        return $this->redirect(['action' => 'index']);
      }
    }

    public function isAuthorized($user) {
      if ($this->request->action === 'add') {
        return true;
      }

      if (in_array($this->request->action, ['edit', 'delete'])) {
        $postId = (int)$this->request->params['pass'][0];
        if ($this->Posts->isOwnedBy($postId, $user['id'])) {
          return true;
        }
      }
      return parent::isAuthorized($user);
    }
  }
?>
