<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{

    /**
     * @var Post
     */
    private $post;

    /**
     * PostController constructor.
     *
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     * @throws \Assurrussa\GridView\Exception\ColumnsException
     * @throws \Assurrussa\GridView\Exception\QueryException
     */
   

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Assurrussa\GridView\Exception\ColumnsException
     * @throws \Assurrussa\GridView\Exception\QueryException
     */
    public function index2()
    {
        $title = 'Posts';
        $data = $this->getGrid()->get();
        if (request()->ajax() || request()->wantsJson()) {
            return $data->toHtml();
        }

        return view('post.index', compact('title', 'data'));
    }

    /**
     * @param int $id
     */
    public function show(int $id)
    {
        dd('show', $id);
    }

    /**
     *
     */
    public function create()
    {
        dd('create');
    }

    /**
     * @param int $id
     */
    public function edit(int $id)
    {
        dd('edit', $id);
    }

    /**
     * @param Request $request
     */
    public function store(Request $request)
    {
        dd('store', $request);
    }

    /**
     * @param Request $request
     * @param int     $id
     */
    public function update(Request $request, int $id)
    {
        dd('update', $request, $id);
    }

    /**
     * @param int $id
     */
    public function destroy(int $id)
    {
        $this->post->newQuery()->findOrFail($id)->delete();
        return back();
    }

    /**
     * @param int $id
     */
    public function restore(int $id)
    {
        $this->post->newQuery()->withTrashed()->findOrFail($id)->restore();
        return back();
    }

    /**
     * @param Request $request
     */
    public function custom(Request $request)
    {
        dd($request->get('custom'));
    }
	
	 public function index()
    {
        $title = 'Posts';
		//return $this->getGrid();
        $data = $this->getGrid()->getSimple();
        if (request()->ajax() || request()->wantsJson()) {
            return $data->toHtml();
        }

        return view('post.index', compact('title', 'data'));
    }

    /**
     * @return \Assurrussa\GridView\GridView|\Illuminate\Foundation\Application|mixed
     */
    public function getGrid()
    {
        // country selected filter
        $nameFilterCountry = 'byCountryId';
        $listCountry = \App\Country::selectRaw('id, name as label')->get()->toArray();
        $listCountrySelected = [];
        if ($cityId = (int)\request()->get($nameFilterCountry)) {
            /** @var \App\Country $model */
            $model = \App\Country::find($cityId);
            $listCountrySelected = [
                'label' => $model->name,
                'id'    => $model->id,
            ];
        }
        // city ajax selected filter
        $nameFilterCity = 'byCityId';
        $listCitySelected = [];
        if ($cityId = (int)\request()->get($nameFilterCity)) {
            /** @var \App\City $city */
            $model = \App\City::find($cityId);
            $listCitySelected = [
                'label' => $model->name,
                'id'    => $model->id,
            ];
        }

        /** @var \Assurrussa\GridView\GridView $gridView */
        $query = $this->post->newQuery()
            ->with([
                'user' => function ($query) {
                    /** @var \App\User $query */
                    $query->with('country', 'city');
                },
            ])
            ->withTrashed();
		//return $query->value('preview');
        $gridView = app(\Assurrussa\GridView\GridView::NAME);
        $gridView->setQuery($query)
            ->setSearchInput(true)
            ->setSortName('id')
            ->setOrderByDesc();

        // classes for every tr string
        $gridView->column()->setClassForString(function ($data) {
            /** @var \App\Post $data */
            return $data->id % 2 ? 'text-success' : '';
        });

        // export setting
        $gridView->setExport(true)
            ->setFieldsForExport([
                'ID'         => 'id',
                0            => 'title',
                'Author'     => function ($data) {
                    /** @var \App\Post $data */
                    return $data->user->name;
                },
                'Country'    => 'user.country.name',
                'Created At' => 'created_at',
            ]);
        $gridView->button()->setButtonExport();

        // columns
        $gridView->column('id', '#')->setSort(true)->setFilterString('byId', '', '', 'width:60px');
        $gridView->column()->setCheckbox();
        $gridView->column('title', 'title')->setFilterString('byTitleLike', '', '', 'width:60px')->setSort(true);
        $gridView->column('preview', 'preview')->setScreening(true)->setHandler(function ($data) {
            /** @var \App\Post $data */
            return '<img src="' . $data->preview . '" alt="' . $data->title . '" widht="60" height="60">';
        });
        $gridView->column('type', 'type')->setFilterSelect('byType', \App\Post::$types)->setSort(false);
        $gridView->column('user.country.name', 'country')->setSort(false)->setScreening(false)
            ->setFilterSelectNotAjax('byCountryId', $listCountry, $listCountrySelected);
        $gridView->column('city_id', 'city')->setSort(false)->setScreening(false)
            ->setFilterSelectAjax($nameFilterCity, [], $listCitySelected, route('city.search'))
            ->setHandler(function ($data) {
                /** @var \App\Post $data */
                return $data->user->city->name;
            });
        $gridView->column('user.name', 'author')->setSort(false)->setScreening(false)->setFilterString('byUserName')
            ->setHandler(function ($data) {
                /** @var \App\Post $data */
                return $data->user->name . ' (id#' . $data->user->id . ')';
            });
        $gridView->column('published_at', 'Published At')->setDateActive(true)
            ->setFilterDateRange('byPublishedAtRange', '', true, 'Y-m-d H:i')
            ->setFilterFormat('DD MMM YY');
        $gridView->column('created_at', 'Created At')->setDateActive(true)
            ->setFilterDate('byCreatedAt', '', true, 'Y-m-d H:i')
            ->setFilterFormat('DD MMM YY');

        // column actions
        $gridView->columnActions(function ($data, $columns) {
            /**
             * @var \App\Post                           $data
             * @var \Assurrussa\GridView\Support\Column $columns
             */
            $columns->addButton()->setActionShow('post.show', [$data->id])
                ->setClass('btn btn-info btn-sm')
                ->setOptions(['target' => '_blank'])
                ->setHandler(function ($data) {
                    /** @var \App\Post $data */
                    return $data->id % 2;
                });
            $columns->addButton()->setActionEdit('post.edit', [$data->id], 'Edit')
                ->setClass('btn btn-outline-primary btn-sm')
                ->setOptions(['target' => '_blank'])
                ->setHandler(function ($data) {
                    /** @var \App\Post $data */
                    return $data->id % 2;
                });
            $columns->addButton()->setActionDelete('post.destroy', [$data->id], '')
                ->setHandler(function ($data) {
                    /** @var \App\Post $data */
                    return $data->id % 2 && !$data->deleted_at;
                });
            $columns->addButton()->setActionRestore('post.restore', [$data->id])
                ->setMethod('PUT')->setHandler(function ($data) {
                    /** @var \App\Post $data */
                    return $data->deleted_at;
                });
        });
//        // column actions
//        $gridView->columnActions(function ($data) use ($gridView) {
//            /** @var \App\Post $data */
//            $buttons = [];
//            $buttons[] = $gridView->columnAction()->setActionShow('post.show', [$data->id])
//                ->setClass('btn btn-info btn-sm')
//                ->setOptions(['target' => '_blank'])
//                ->setHandler(function ($data) {
//                    /** @var \App\Post $data */
//                    return $data->id % 2;
//                });
//            $buttons[] = $gridView->columnAction()->setActionEdit('post.edit', [$data->id], 'Edit')
//                ->setClass('btn btn-outline-primary btn-sm')
//                ->setOptions(['target' => '_blank'])
//                ->setHandler(function ($data) {
//                    /** @var \App\Post $data */
//                    return $data->id % 2;
//                });
//            $buttons[] = $gridView->columnAction()->setActionDelete('post.destroy', [$data->id], '')
//                ->setHandler(function ($data) {
//                    /** @var \App\Post $data */
//                    return $data->id % 2 && !$data->deleted_at;
//                });
//            $buttons[] = $gridView->columnAction()->setActionRestore('post.restore', [$data->id])
//                ->setMethod('PUT')->setHandler(function ($data) {
//                    /** @var \App\Post $data */
//                    return $data->deleted_at;
//                });
//            return $buttons;
//        });

        // create button
        $gridView->button()->setButtonCreate(route('post.create'));
        // create custom button
        $gridView->button()->setButtonCheckboxAction(route('post.custom'), '?custom=');

        return $gridView;
    }
}
