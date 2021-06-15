<?php

namespace App\Exports;

use App\Models\Book;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromCollection, WithMapping, WithHeadings
{

    /**
     * @var array|mixed
     */
    private $headings = [];

    public function __construct($headings = [])
    {
        $this->headings = $headings;

    }

    /**
     * @return Collection
     */
    public function collection()
    {
        if (!$this->headings)
            return Book::all();
        else
            return Book::all($this->headings);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if (!$this->headings) {
            return [
                trans('admin.book.columns.author'),
                trans('admin.book.columns.id'),
                trans('admin.book.columns.title'),
            ];
        } else
            return $this->headings;
    }

    /**
     * @param Book $book
     * @return array
     *
     */
    public function map($book): array
    {
        if (!$this->headings) {
            return [
                $book->author,
                $book->id,
                $book->title,
            ];
        } else if (isset($book->author)) {
            return [
                $book->id,
                $book->author
            ];
        }
        else{
            return [
                $book->id,
                $book->title,
            ];
        }
    }
}
