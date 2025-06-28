<?php
class RenderCategoryEvent extends AbstractEvent {

    public int $id;
    public string $name;
    public string $content;

    public function __construct(int $id, string $name, string $content) {
        $this->id = $id;
        $this->name = $name;
        $this->content = $content;
    }

    public static function name(): string {
        return 'renderCategory';
    }

    public static function description(): string {
        return 'renderCategory';
    }

    public static function internal(): bool {
        return true;
    }

    public static function return(): bool {
        return true;
    }
}