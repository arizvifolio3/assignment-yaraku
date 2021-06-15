<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BooksExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Book\BulkDestroyBook;
use App\Http\Requests\Admin\Book\DestroyBook;
use App\Http\Requests\Admin\Book\IndexBook;
use App\Http\Requests\Admin\Book\StoreBook;
use App\Http\Requests\Admin\Book\UpdateBook;
use App\Http\Requests\Admin\Book\ExportBook as RequestExport;
use App\Models\Book;
use Brackets\AdminListing\Facades\AdminListing;
use Exception;
use http\Env\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;
use ACFBentveld\XML\XML;

class BooksController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param IndexBook $request
     * @return array|Factory|View
     */
    public function index(IndexBook $request)
    {
        // create and AdminListing instance for a specific model and
        $data = AdminListing::create(Book::class)->processRequestAndGet(
        // pass the request with params
            $request,

            // set columns to query
            ['author', 'id', 'title'],

            // set columns to searchIn
            ['author', 'title']
        );

        if ($request->ajax()) {
            if ($request->has('bulk')) {
                return [
                    'bulkItems' => $data->pluck('id')
                ];
            }
            return ['data' => $data];
        }

        $exportOptions = [
            'csv' => [
                'With Title and Author',
                'With only Titles',
                'With only Authors',
            ],
            'xml' => [
                'With Title and Author',
                'With only Titles',
                'With only Authors',
            ],
        ];
        return view('admin.book.index', ['data' => $data, 'exportData' => $exportOptions]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function create()
    {
        $this->authorize('admin.book.create');

        return view('admin.book.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBook $request
     * @return array|RedirectResponse|Redirector
     */
    public function store(StoreBook $request)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Store the Book
        $book = Book::create($sanitized);

        if ($request->ajax()) {
            return ['redirect' => url('admin/books'), 'message' => trans('brackets/admin-ui::admin.operation.succeeded')];
        }

        return redirect('admin/books');
    }

    /**
     * Display the specified resource.
     *
     * @param Book $book
     * @return void
     * @throws AuthorizationException
     */
    public function show(Book $book)
    {
        $this->authorize('admin.book.show', $book);

        // TODO your code goes here
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Book $book
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function edit(Book $book)
    {
        $this->authorize('admin.book.edit', $book);


        return view('admin.book.edit', [
            'book' => $book,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateBook $request
     * @param Book $book
     * @return array|RedirectResponse|Redirector
     */
    public function update(UpdateBook $request, Book $book)
    {
        // Sanitize input
        $sanitized = $request->getSanitized();

        // Update changed values Book
        $book->update($sanitized);

        if ($request->ajax()) {
            return [
                'redirect' => url('admin/books'),
                'message' => trans('brackets/admin-ui::admin.operation.succeeded'),
            ];
        }

        return redirect('admin/books');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DestroyBook $request
     * @param Book $book
     * @return ResponseFactory|RedirectResponse|Response
     * @throws Exception
     */
    public function destroy(DestroyBook $request, Book $book)
    {
        $book->delete();

        if ($request->ajax()) {
            return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resources from storage.
     *
     * @param BulkDestroyBook $request
     * @return Response|bool
     * @throws Exception
     */
    public function bulkDestroy(BulkDestroyBook $request): Response
    {
        DB::transaction(static function () use ($request) {
            collect($request->data['ids'])
                ->chunk(1000)
                ->each(static function ($bulkChunk) {
                    Book::whereIn('id', $bulkChunk)->delete();

                    // TODO your code goes here
                });
        });

        return response(['message' => trans('brackets/admin-ui::admin.operation.succeeded')]);
    }


    public function export(RequestExport $request)
    {
        if ($request->has('xml')) {
            switch ($request->get('xml')) {
                case 1 :
                    $xml = XML::export(['book' => Book::all(['id', 'title'])->makeHidden(['resource_url'])->toArray()])
                        ->rootTag('books')
                        ->toString();
                    break;
                case 2 :
                    $xml = XML::export(['book' => Book::all(['id', 'author'])->makeHidden(['resource_url'])->toArray()])
                        ->rootTag('books')
                        ->toString();
                    break;
                default:
                    $xml = XML::export(['book' => Book::all(['id', 'title', 'author'])->makeHidden(['resource_url'])->toArray()])
                        ->rootTag('books')
                        ->toString();

            }

            return $response = response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="books.xml"',
            ]);
        } else if ($request->has('csv')) {
            switch ($request->get('csv')) {
                case 1 :
                    $books = new BooksExport(['id', 'title']);
                    break;
                case 2 :
                    $books = new BooksExport(['id', 'author']);
                    break;
                default:
                    $books = new BooksExport();
            }

            return Excel::download($books, 'books.csv', \Maatwebsite\Excel\Excel::CSV);
        }

    }
}
