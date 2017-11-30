<?php
class IndexAction extends HomeAction {
    public function index() {
        $this->show("<pre>" . file_get_contents("README.md"));
    }
}