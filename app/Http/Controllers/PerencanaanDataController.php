<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InformasiUmumService;
use App\Services\IdentifikasiKebutuhanService;
use App\Services\ShortlistVendorService;
use App\Services\PerencanaanDataService;
use App\Services\GeneratePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PerencanaanDataController extends Controller
{
    protected $informasiUmumService;
    protected $IdentifikasiKebutuhanService;
    protected $shortlistVendorService;
    protected $perencanaanDataService;
    protected $generatePdfService;

    public function __construct(
        InformasiUmumService $informasiUmumService,
        IdentifikasiKebutuhanService $IdentifikasiKebutuhanService,
        ShortlistVendorService $shortlistVendorService,
        PerencanaanDataService $perencanaanDataService,
        GeneratePdfService $generatePdfService
    ) {
        $this->informasiUmumService = $informasiUmumService;
        $this->IdentifikasiKebutuhanService = $IdentifikasiKebutuhanService;
        $this->shortlistVendorService = $shortlistVendorService;
        $this->perencanaanDataService = $perencanaanDataService;
        $this->generatePdfService = $generatePdfService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDataInformasiUmumById($id)
    {
        try {
            $getDataInformasiUmum = $this->informasiUmumService->getDataInformasiUmumById($id);
            if (!$getDataInformasiUmum) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data informasi umum id ' . $id . ' ditemukan!',
                    'data' => $getDataInformasiUmum
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data informasi umum id ' . $id . ' tidak ditemukan!',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function storeInformasiUmumData(Request $request)
    {
        $rules = [
            'tipe_informasi_umum' => 'required',
            'nama_paket' => 'required',
            'nama_ppk' => 'required',
            'jabatan_ppk' => 'required',
        ];
        if ($request->tipe_informasi_umum == 'manual') {
            $rules = array_merge($rules, [
                'nama_balai' => 'required',
                //'tipologi' => 'required',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'validasi gagal!',
                'data' => []
            ], 404);
        }

        // $checkNamaPaket = $this->informasiUmumService->checkNamaPaket($request->nama_paket);
        // if ($checkNamaPaket) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'paket ' . $request->nama_paket . ' sudah / sedang diproses!',
        //         'data' => []
        //     ]);
        // }

        try {
            $saveInformasiUmum = $this->informasiUmumService->saveInformasiUmum($request);
            if (!$saveInformasiUmum) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan data!',
                    'data' => []
                ]);
            }
            //change status
            $this->perencanaanDataService->changeStatusPerencanaanData(config('constants.STATUS_PERENCANAAN'), $saveInformasiUmum['id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'data' => $saveInformasiUmum
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan pengguna',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getInformasiUmumByPerencanaanId($id)
    {
        try {
            $perencanaanData = $this->informasiUmumService->getInformasiUmumByPerencanaanId($id);
            if (!$perencanaanData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mendapatkan data dengan id ' . $id,
                    'data' => []
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil didapat',
                'data' => $perencanaanData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan pengguna',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function storeIdentifikasiKebutuhan(Request $request)
    {

        try {
            $identifikasiKebutuhanId = $request->informasi_umum_id;
            $materialResult = [];
            foreach ($request->material as $material) {
                $materialResult[] = $this->IdentifikasiKebutuhanService->storeMaterial($material, $identifikasiKebutuhanId);
            }

            $peralatanResult = [];
            foreach ($request->peralatan as $peralatan) {
                $peralatanResult[] = $this->IdentifikasiKebutuhanService->storePeralatan($peralatan, $identifikasiKebutuhanId);
            }

            $tenagaKerjaResult = [];
            foreach ($request->tenaga_kerja as $tenagaKerja) {
                $tenagaKerjaResult[] = $this->IdentifikasiKebutuhanService->storeTenagaKerja($tenagaKerja, $identifikasiKebutuhanId);
            }

            //update to perencanaan_data table
            $this->perencanaanDataService->updatePerencanaanData($identifikasiKebutuhanId, 'identifikasi_kebutuhan', $identifikasiKebutuhanId);
            $this->perencanaanDataService->changeStatusPerencanaanData(config('constants.STATUS_PERENCANAAN'), $identifikasiKebutuhanId);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan!',
                'data' => [
                    'material' => $materialResult,
                    'peralatan' => $peralatanResult,
                    'tenaga_kerja' => $tenagaKerjaResult,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data!',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAllDataVendor($identifikasiKebutuhanId)
    {
        $dataVendor = $this->shortlistVendorService->getDataVendor($identifikasiKebutuhanId);
        if ($dataVendor) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil didapat!',
                'data' => $dataVendor
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak dapat ditemukan!',
            'data' => []
        ], 404);
    }

    public function getSearchDataVendors($identifikasiKebutuhanId, Request $request)
    {
        $dataVendor = $this->shortlistVendorService->getDataVendor($identifikasiKebutuhanId);
        if ($dataVendor) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil didapat!',
                'data' => $dataVendor
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data tidak dapat ditemukan!',
            'data' => []
        ], 404);
    }

    public function selectDataVendor(Request $request)
    {
        $rules = [
            'shortlist_vendor' => 'required|array',
            'shortlist_vendor.*.data_vendor_id' => 'required',
            'shortlist_vendor.*.nama_vendor' => 'required',
            'shortlist_vendor.*.pemilik_vendor' => 'required',
            'shortlist_vendor.*.alamat' => 'required',
            'shortlist_vendor.*.kontak' => 'required',
            'shortlist_vendor.*.sumber_daya' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed!',
                'errors' => $validator->errors()
            ]);
        }

        DB::beginTransaction();
        try {
            $shortlistVendorId = $request->identifikasi_kebutuhan_id;
            $dataShortlistvendor = [];
            foreach ($request->shortlist_vendor as $shortlistVendor) {
                // * shortListVendorId = identifikasi_kebutuhan_id from table "informasi_umum";
                $dataShortlistvendor[] = $this->shortlistVendorService->storeShortlistVendor($shortlistVendor,  $shortlistVendorId);
            }

            $this->perencanaanDataService->updatePerencanaanData($shortlistVendorId, 'shortlist_vendor', $shortlistVendorId);
            $this->perencanaanDataService->changeStatusPerencanaanData(config('constants.STATUS_PERENCANAAN'), $shortlistVendorId);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan!',
                'shortlist_vendor_id' => $shortlistVendorId,
                'data' => [
                    'shortlist_vendor' => $dataShortlistvendor,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data!',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function informasiUmumResult(Request $request)
    {
        $getInformasiUmum = $this->perencanaanDataService->listAllPerencanaanData($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil didapat!',
            'data' => $getInformasiUmum,
        ]);
    }

    public function identifikasiKebutuhanResult(Request $request)
    {
        $getMaterial = $this->IdentifikasiKebutuhanService->getIdentifikasiKebutuhanByPerencanaanId('material', $request);
        $getPeralatan = $this->IdentifikasiKebutuhanService->getIdentifikasiKebutuhanByPerencanaanId('peralatan', $request);
        $getTenagaKerja = $this->IdentifikasiKebutuhanService->getIdentifikasiKebutuhanByPerencanaanId('tenaga_kerja', $request);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil didapat!',
            'data' => [
                'material' => $getMaterial,
                'peralatan' => $getPeralatan,
                'tenaga_kerja' => $getTenagaKerja,
            ],
        ]);
    }

    public function shortlistVendorResult(Request $request)
    {
        $getShortlistVendor = $this->shortlistVendorService->getShortlistVendorResult($request);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil didapat!',
            'data' => $getShortlistVendor,
        ]);
    }

    public function perencanaanDataResult(Request $request)
    {
        $informasiUmumId = $request->query('id');

        $data = $this->perencanaanDataService->listAllPerencanaanData($informasiUmumId);

        if (!isset($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'data tidak ditemukan!',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil didapat!',
            'data' => $data
        ]);
    }

    public function adjustShortlistVendor(Request $request)
    {
        $rules = [
            'id_vendor' => 'required',
            'shortlist_vendor_id' => 'required',

        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed!',
                'errors' => $validator->errors()
            ]);
        }

        try {
            $shortlistVendorId = $request['shortlist_vendor_id']; // id dari identifikasi kebutuhan
            $vendorId = $request['id_vendor'];
            $material = [];
            $peralatan = [];
            $tenagaKerja = [];

            if (count($request['material'])) {
                foreach ($request['material'] as $item) {
                    $material[] = $item['id'];
                }
            }

            if (count($request['peralatan'])) {
                foreach ($request['peralatan'] as $item) {
                    $peralatan[] = $item['id'];
                }
            }

            if (count($request['tenaga_kerja'])) {
                foreach ($request['tenaga_kerja'] as $item) {
                    $tenagaKerja[] = $item['id'];
                }
            }

            $saveData = $this->shortlistVendorService->saveKuisionerPdfData($vendorId, $shortlistVendorId, $material, $peralatan, $tenagaKerja);
            if (count($saveData)) {
                $generatePdf = $this->generatePdfService->generatePdfMaterial($saveData);
                $savePdf = $this->shortlistVendorService->saveUrlPdf($vendorId, $shortlistVendorId, $generatePdf);
                if (isset($saveData)) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Data berhasil didapat!',
                        'data' => $savePdf,
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data!',
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data!',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getShortlistVendorSumberDaya(Request $request)
    {
        $idInformasiUmum = $request->query('informasi_umum_id');
        $idShortlistVendor = $request->query('id');

        if (!$idInformasiUmum || !$idShortlistVendor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing required parameters',
                'data' => null
            ], 400);
        }

        $queryData = $this->shortlistVendorService->getIdentifikasiByShortlist($idShortlistVendor, $idInformasiUmum);

        // Check if queryData is null
        if (is_null($queryData)) {
            return response()->json([
                'status' => 'success',
                'message' => 'No data found',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil didapat!',
            'data' => $queryData
        ], 200);
    }

    public function getIdentifikasiKebutuhanStored($informasiUmumId)
    {
        $perencanaanData = $this->perencanaanDataService->listAllPerencanaanData($informasiUmumId);
        if (!empty($perencanaanData)) {
            return response()->json([
                'status' => 'success',
                'message' => config('constants.SUCCESS_MESSAGE_GET'),
                'data' => [
                    'material' => $perencanaanData['material'],
                    'peralatan' => $perencanaanData['peralatan'],
                    'tenaga_kerja' => $perencanaanData['tenagaKerja'],
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => config('constants.ERROR_MESSAGE_GET'),
                'data' => []
            ]);
        }
    }

    public function changeStatusPerencanaan($informasiUmumId)
    {
        try {
            $changeStatus = $this->perencanaanDataService->changeStatusPerencanaanData(config('constants.STATUS_PENGUMPULAN'), $informasiUmumId);

            if ($changeStatus) {
                return response()->json([
                    'status' => 'success',
                    'message' => config('constants.SUCCESS_MESSAGE_SAVE'),
                    'data' => $changeStatus
                ]);
            }

            return response()->json([
                'status' => 'gagal',
                'message' => "Data tidak ditemukan"
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => config('constants.ERROR_MESSAGE_SAVE'),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function tableListPerencanaan()
    {
        $status = [
            config('constants.STATUS_PERENCANAAN'),
            config('constants.STATUS_PENYEBARLUASAN_DATA'),

        ];
        $list = $this->perencanaanDataService->tableListPerencanaanData($status);
        if (isset($list)) {
            return response()->json([
                'status' => 'success',
                'message' => config('constants.SUCCESS_MESSAGE_GET'),
                'data' => $list
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => config('constants.ERROR_MESSAGE_GET'),
                'data' => []
            ], 404);
        }
    }

    /**
     * Get public perencanaan data with advanced filtering, sorting, and pagination
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicPerencanaanData(Request $request)
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'region' => 'nullable|string|max:20',
                'period' => 'nullable|integer|min:2020|max:2030',
                'city' => 'nullable|string|max:20',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:created_at,period_year,city_code,status',
                'sort_order' => 'nullable|string|in:asc,desc',
                'status' => 'nullable|string|in:draft,review,approved,completed',
                'search' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get validated input
            $regionCode = $request->query('region', env('ORG_REGION_CODE', 'default'));
            $period = $request->query('period');
            $cityCode = $request->query('city');
            $perPage = $request->query('per_page', 10);
            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');
            $status = $request->query('status');
            $search = $request->query('search');

            // Build query with optimized eager loading
            $query = \App\Models\PerencanaanData::with([
                'informasiUmum:id,nama_paket,nama_ppk,nama_balai',
                'material:id,identifikasi_kebutuhan_id,nama_material',
                'peralatan:id,identifikasi_kebutuhan_id,nama_peralatan',
                'tenagaKerja:id,identifikasi_kebutuhan_id,jenis_tenaga_kerja'
            ]);

            // Apply filters
            if ($regionCode && $regionCode !== 'default') {
                $query->where('region_code', $regionCode);
            }

            if ($period) {
                $query->where('period_year', $period);
            }

            if ($cityCode) {
                $query->where('city_code', 'LIKE', "%{$cityCode}%");
            }

            if ($status) {
                $query->where('status', $status);
            }

            // Apply search across related models
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('informasiUmum', function ($iq) use ($search) {
                        $iq->where('nama_paket', 'LIKE', "%{$search}%")
                           ->orWhere('nama_ppk', 'LIKE', "%{$search}%")
                           ->orWhere('nama_balai', 'LIKE', "%{$search}%");
                    })
                    ->orWhere('city_code', 'LIKE', "%{$search}%")
                    ->orWhere('region_code', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting with fallback
            $allowedSortFields = ['created_at', 'period_year', 'city_code', 'status'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Get data with pagination
            $data = $query->paginate($perPage);

            // Format response data
            $formattedData = $data->getCollection()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'region_code' => $item->region_code,
                    'period_year' => $item->period_year,
                    'city_code' => $item->city_code,
                    'status' => $item->status ?? 'draft',
                    'created_at' => $item->created_at->toISOString(),
                    'updated_at' => $item->updated_at->toISOString(),
                    'informasi_umum' => $item->informasiUmum ? [
                        'nama_paket' => $item->informasiUmum->nama_paket,
                        'nama_ppk' => $item->informasiUmum->nama_ppk,
                        'nama_balai' => $item->informasiUmum->nama_balai,
                    ] : null,
                    'resource_counts' => [
                        'material' => $item->material->count(),
                        'peralatan' => $item->peralatan->count(),
                        'tenaga_kerja' => $item->tenagaKerja->count(),
                    ]
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Public perencanaan data retrieved successfully',
                'data' => [
                    'data' => $formattedData,
                    'current_page' => $data->currentPage(),
                    'first_page_url' => $data->url(1),
                    'last_page' => $data->lastPage(),
                    'last_page_url' => $data->url($data->lastPage()),
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
                'filters' => [
                    'region' => $regionCode,
                    'period' => $period,
                    'city' => $cityCode,
                    'status' => $status,
                    'search' => $search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'meta' => [
                    'total_regions' => \App\Models\PerencanaanData::distinct('region_code')->count(),
                    'total_periods' => \App\Models\PerencanaanData::distinct('period_year')->count(),
                    'available_periods' => \App\Models\PerencanaanData::distinct('period_year')
                        ->orderBy('period_year', 'desc')
                        ->pluck('period_year')
                        ->values(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve public perencanaan data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve public perencanaan data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Export public perencanaan data to Excel/CSV
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPublicPerencanaanData(Request $request)
    {
        try {
            // Validate request parameters (same as getPublicPerencanaanData)
            $validator = Validator::make($request->all(), [
                'region' => 'nullable|string|max:20',
                'period' => 'nullable|integer|min:2020|max:2030',
                'city' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:draft,review,approved,completed',
                'search' => 'nullable|string|max:255',
                'format' => 'nullable|string|in:excel,csv',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid request parameters',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get validated input
            $regionCode = $request->query('region', env('ORG_REGION_CODE', 'default'));
            $period = $request->query('period');
            $cityCode = $request->query('city');
            $status = $request->query('status');
            $search = $request->query('search');
            $format = $request->query('format', 'excel');

            // Build query (same logic as getPublicPerencanaanData but without pagination)
            $query = \App\Models\PerencanaanData::with([
                'informasiUmum:id,nama_paket,nama_ppk,nama_balai',
                'material:id,identifikasi_kebutuhan_id,nama_material',
                'peralatan:id,identifikasi_kebutuhan_id,nama_peralatan',
                'tenagaKerja:id,identifikasi_kebutuhan_id,jenis_tenaga_kerja'
            ]);

            // Apply same filters as getPublicPerencanaanData
            if ($regionCode && $regionCode !== 'default') {
                $query->where('region_code', $regionCode);
            }
            if ($period) {
                $query->where('period_year', $period);
            }
            if ($cityCode) {
                $query->where('city_code', 'LIKE', "%{$cityCode}%");
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('informasiUmum', function ($iq) use ($search) {
                        $iq->where('nama_paket', 'LIKE', "%{$search}%")
                           ->orWhere('nama_ppk', 'LIKE', "%{$search}%")
                           ->orWhere('nama_balai', 'LIKE', "%{$search}%");
                    })
                    ->orWhere('city_code', 'LIKE', "%{$search}%")
                    ->orWhere('region_code', 'LIKE', "%{$search}%");
                });
            }

            // Get all data (no pagination for export)
            $data = $query->orderBy('created_at', 'desc')->get();

            // Prepare export data
            $exportData = $data->map(function ($item, $index) {
                return [
                    'No' => $index + 1,
                    'Nama Paket' => $item->informasiUmum?->nama_paket ?? '-',
                    'PPK' => $item->informasiUmum?->nama_ppk ?? '-',
                    'Balai' => $item->informasiUmum?->nama_balai ?? '-',
                    'Periode' => $item->period_year,
                    'Kode Kota' => $item->city_code ?? '-',
                    'Status' => ucfirst($item->status ?? 'draft'),
                    'Tanggal Dibuat' => $item->created_at->format('d/m/Y H:i'),
                    'Jumlah Material' => $item->material->count(),
                    'Jumlah Peralatan' => $item->peralatan->count(),
                    'Jumlah Tenaga Kerja' => $item->tenagaKerja->count(),
                ];
            });

            $filename = 'perencanaan_data_' . date('Y-m-d_H-i-s');

            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
                ];

                $callback = function () use ($exportData) {
                    $file = fopen('php://output', 'w');

                    // Add BOM for UTF-8
                    fwrite($file, "\xEF\xBB\xBF");

                    // Add headers
                    if ($exportData->isNotEmpty()) {
                        fputcsv($file, array_keys($exportData->first()));
                    }

                    // Add data
                    foreach ($exportData as $row) {
                        fputcsv($file, $row);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } else {
                // For Excel format, you would need maatwebsite/excel package
                // For now, return JSON response indicating Excel export is not yet implemented
                return response()->json([
                    'status' => 'error',
                    'message' => 'Excel export not yet implemented. Please use CSV format.',
                    'suggestion' => 'Add format=csv to your request'
                ], 501);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to export public perencanaan data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
