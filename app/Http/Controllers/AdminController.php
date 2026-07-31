<?php

namespace App\Http\Controllers;

use App\Actions\Admin\ActivityLogs\GetActivityLogDetailsAction;
use App\Actions\Admin\ActivityLogs\GetActivityLogsAction;
use App\Actions\Admin\Backup\BackupDatabaseAction;
use App\Actions\Admin\Backup\DownloadBackupAction;
use App\Actions\Admin\Backup\ListBackupsAction;
use App\Actions\Admin\Backup\RestoreDatabaseAction;
use App\Actions\Admin\Events\AssignHallToEventAction;
use App\Actions\Admin\Events\CreateEventSlotAction;
use App\Actions\Admin\Events\GetSlotsTimelineAction;
use App\Actions\Admin\Halls\AttachSectorToHallAction;
use App\Actions\Admin\Halls\AutoAssignBoothsAction;
use App\Actions\Admin\Halls\CreateBoothAction;
use App\Actions\Admin\Halls\CreateHallAction;
use App\Actions\Admin\Halls\DeleteBoothAction;
use App\Actions\Admin\Halls\DeleteHallAction;
use App\Actions\Admin\Halls\DetachSectorFromHallAction;
use App\Actions\Admin\Halls\GetHallDetailsAction;
use App\Actions\Admin\Halls\GetHallsAction;
use App\Actions\Admin\Halls\NullBoothAction;
use App\Actions\Admin\Halls\UpdateHallAction;
use App\Actions\Admin\joinedCompanies\ActiveCompanyStatusAction;
use App\Actions\Admin\joinedCompanies\AssignBoothToCompanyAction;
use App\Actions\Admin\joinedCompanies\GetJoinedCompaniesAction;
use App\Actions\Admin\joinedCompanies\GetJoinedCompanyDetailsAction;
use App\Actions\Admin\joinedCompanies\GetSuitableBoothsAction;
use App\Actions\Admin\RequestsScreen\GetCompanyRequestDetailAction;
use App\Actions\Admin\RequestsScreen\GetCompanyRequestsAction;
use App\Actions\Admin\RequestsScreen\GetEventRequestDetailsAction;
use App\Actions\Admin\RequestsScreen\GetEventRequestsAction;
use App\Actions\Admin\RequestsScreen\UpdateCompanyRequestStatusAction;
use App\Actions\Admin\Sectors\CreateSectorAction;
use App\Actions\Admin\Sectors\DeleteSectorAction;
use App\Actions\Admin\Statistics\GetDashboardStatisticsAction;
use App\Actions\Notification\DeleteNotificationAction;
use App\Actions\Notification\GetAdminNotificationsAction;
use App\Actions\Notification\MarkNotificationAsReadAction;
use App\Actions\Visitor\HomePage\GetSectorsAction;
use App\Http\Requests\Admin\AddBoothRequest;
use App\Http\Requests\Admin\AddHallRequest;
use App\Http\Requests\Admin\AddSlotRequest;
use App\Http\Requests\Admin\OrderRequest;
use App\Http\Requests\Admin\UpdateHallRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\SectorResource;
use App\Models\Company;
use App\Models\CompanyRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;
    public function getCompanyRequests(OrderRequest $request, GetCompanyRequestsAction $action): JsonResponse
    {
        $data = $action->execute($request->validated());

        return $this->success($data, 'Company requests fetched successfully');
    }
    public function getCompanyDetails(int $id, GetCompanyRequestDetailAction $action): JsonResponse
    {
        $data = $action->execute($id);

        return $this->success($data, 'Company request details fetched successfully');
    }
    public function updateCompanyRequestStatus(Request $request, UpdateCompanyRequestStatusAction $action)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:company_requests,id',
            'status' => 'required|in:approved,rejected,pending,action_required',
            'admin_notes' => 'nullable|string',
        ]);

        $action->execute($validated['request_id'], $validated['status'], $validated['admin_notes']);
        return $this->success(null, 'Company status updated successfully');
    }
    public function getEventRequests(OrderRequest $request, GetEventRequestsAction $action): JsonResponse
    {
        $data = $action->execute($request->validated());

        return $this->success($data, 'Event requests fetched successfully');
    }
    public function getEventRequestDetails(int $id, GetEventRequestDetailsAction $action): JsonResponse
    {
        $data = $action->execute($id);

        return $this->success($data, 'Event request details fetched successfully');
    }

    /////////////////////////////////////// events
    /*public function approveEventRequest(Request $request, ApproveEventRequestAction $action): JsonResponse
    {
        $request->validate([
            'request_id' => 'required|integer|exists:event_requests,id',
            'hall_id'    => 'required|integer|exists:halls,id'
        ]);

        try {
            // 2. تمرير المعاملات من الـ Body مباشرة إلى الأكشن
            $eventRequest = $action->execute(
                $request->input('request_id'),
                $request->input('hall_id')
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'تم قبول الفعالية وإسناد القاعة واحتساب الرسوم بنجاح.',
                'data'    => [
                    'total_price'      => $eventRequest->total_price,
                    'required_deposit' => $eventRequest->required_deposit,
                    'payment_due'      => $eventRequest->payment_due_date->format('Y-m-d H:i')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }*/

    /////////////////////////////////////// sectors
    public function getSectors(GetSectorsAction $action): JsonResponse
    {
        $sectors = $action->execute('en');

        return $this->success(SectorResource::collection($sectors), 'Sectors fetched successfully');
    }

    public function addSector(Request $request, CreateSectorAction $action): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string'
        ]);

        $action->execute($data);
        return $this->success(null, 'Sector created successfully');
    }

    public function deleteSector(int $id, DeleteSectorAction $action): JsonResponse
    {
        try {
            $action->execute($id);
            return $this->success(null, 'Sector deleted successfully');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422); // كود 422 يعبر عن Unprocessable Entity بسبب الارتباطات
        }
    }


    /////////////////////////////////////// hall screen >> halls and sectors and booths

    public function getHalls(GetHallsAction $action)
    {
        $data = $action->execute();
        return $this->success($data, 'Halls fetched successfully');
    }

    public function getHallDetails(int $id,GetHallDetailsAction $action)
    {
        $data = $action->execute($id);
        return $this->success($data, 'Hall Details fetched successfully');
    }

    public function addHall(AddHallRequest $request, CreateHallAction $action)
    {
        $hall = $action->execute($request->validated());
        return $this->success($hall, 'Hall added successfully');
    }

    public function updateHall(UpdateHallRequest $request, UpdateHallAction $action)
    {
        $hall = $action->execute($request->validated());
        return $this->success($hall, 'Hall updated successfully');
    }

    public function deleteHall(int $id, DeleteHallAction $action): JsonResponse
    {
        $action->execute($id);
        return $this->success(null, 'Hall deleted successfully');
    }

    public function attachSector(Request $request, AttachSectorToHallAction $action)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'hall_id' => 'required|exists:halls,id']);

        $action->execute($request->hall_id, $request->sector_id);
        return $this->success(null, 'Sector attached to hall successfully');
    }

    public function detachSector(Request $request, DetachSectorFromHallAction $action): JsonResponse
    {
        $request->validate([
            'sector_id' => 'required|exists:sectors,id',
            'hall_id' => 'required|exists:halls,id'
        ]);

        $action->execute($request->hall_id, $request->sector_id);
        return $this->success(null, 'sector unsigned successfully');
    }

    public function addBooth(AddBoothRequest $request, CreateBoothAction $action)
    {
        $booth = $action->execute($request->validated());
        return $this->success($booth, 'Booth created successfully');
    }

    public function deleteBooth(int $id, DeleteBoothAction $action)
    {
        $action->execute($id);
        return $this->success(null, 'Booth deleted successfully');
    }

    public function nullBooth(int $id, NullBoothAction $action)
    {
        $action->execute($id);
        return $this->success(null, 'Booth being NULL successfully');
    }

    public function autoAssign(AutoAssignBoothsAction $action): JsonResponse
    {
        $results = $action->execute();
        return $this->success($results, 'Auto assign done successfully');
    }

    ////////////////////////////////////////// joined companies

    public function getJoinedCompanies(Request $request, GetJoinedCompaniesAction $action)
    {
        $companies = $action->execute($request->query('search'));
        return $this->success($companies, 'Joined Companies Fetched successfully');
    }

    public function JoinedCompanyDetails(int $id, GetJoinedCompanyDetailsAction $action): JsonResponse
    {
        $companyDetails = $action->execute($id);
        return $this->success($companyDetails, 'Joined Companies Details Fetched successfully');
    }

    public function ActiveStatus(Company $company, ActiveCompanyStatusAction $action): JsonResponse
    {
        $status = $action->execute($company);
        return $this->success(null, $status ? 'The company has been successfully activated.' : 'The company was successfully shut down and its access was blocked.');
    }

    public function suitableBooths(CompanyRequest $company, GetSuitableBoothsAction $action): JsonResponse
    {
        $booths = $action->execute($company);
        return $this->success($booths, 'Booths Fetched successfully');
    }

    public function assignBooth(Request $request, AssignBoothToCompanyAction $action): JsonResponse
    {
        $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'company_id' => 'required|exists:companies,id',
            'company_request_id' => 'required|exists:company_requests,id']);

        $action->execute($request->booth_id, $request->company_id, $request->company_request_id);
        return $this->success(null, 'Booth assigned successfully');

    }

    public function timeline(GetSlotsTimelineAction $action): JsonResponse
    {
        $timeline = $action->execute();
        return $this->success($timeline, 'Events TimeLine fetched successfully');

    }

    public function assignHall(Request $request, AssignHallToEventAction $action): JsonResponse
    {
        $request->validate([
            'event_id' => 'required|exists:event_requests,id',
            'hall_id' => 'required|exists:halls,id'
        ]);

        $action->execute($request->event_id, $request->hall_id);
        return $this->success(null, 'Hall assigned to event successfully');

    }

    public function addSlot(AddSlotRequest $request, CreateEventSlotAction $action): JsonResponse
    {
        $action->execute($request->validated());
        return $this->success(null, 'Slot Created successfully');

    }


    /////////////////////////////////////////////////////////////
    /// notifications

    public function getNotifications(Request $request, GetAdminNotificationsAction $action): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|in:company_request,event_request,visitor_complaint,company_complaint,system_error',
            'status' => 'nullable|in:read,unread,archived',
            'time' => 'nullable|date_format:H:i',
        ]);

        $data = $action->execute($validated);
        return $this->success($data, 'notification fetched successfully');
    }

    public function markAsRead(int $id, MarkNotificationAsReadAction $action): JsonResponse
    {
        $action->execute($id);
        return $this->success(null, 'Notification marked as read');
    }

    public function deleteNotification(int $id, DeleteNotificationAction $action): JsonResponse
    {
        $action->execute($id);
        return $this->success(null, 'Notification deleted');
    }

    ///////////////////////////////////////////////////////

    public function getDashboardStats(GetDashboardStatisticsAction $action): JsonResponse
    {
        $statistics = $action->execute();
        return $this->success($statistics, 'Dashboard statistics fetched successfully');
    }

    ///////////////////////////////////activity log
    public function getActivityLogs(Request $request, GetActivityLogsAction $action): JsonResponse
    {
        $activities = $action->execute($request);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب سجل النشاطات بنجاح',
            'data'    => ActivityLogResource::collection($activities)
        ]);
    }

    /**
     * عرض تفاصيل سجل نشاط محدد
     */
    public function showActivityLog($id, GetActivityLogDetailsAction $action): JsonResponse
    {
        $activity = $action->execute($id);

        if (!$activity) {
            return response()->json([
                'success' => false,
                'message' => 'السجل غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ActivityLogResource($activity)
        ]);
    }

    ///////////////////////////////buckup
    public function backup(BackupDatabaseAction $action): JsonResponse
    {
        try {
            $s3Path = $action->execute();

            return response()->json([
                'status'  => 'success',
                'message' => 'Database backup created and uploaded to S3 successfully.',
                's3_path' => $s3Path,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to backup database. Please check system logs.',
            ], 500);
        }
    }

    public function getBackupFiles(ListBackupsAction $action): JsonResponse
    {
        $backups = $action->execute();

        return response()->json([
            'success' => true,
            'data'    => $backups,
        ]);
    }

    public function downloadBackup(Request $request, DownloadBackupAction $action): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => 'required|string',
        ]);

        $downloadUrl = $action->execute($validated['file_name']);

        return response()->json([
            'success'      => true,
            'download_url' => $downloadUrl,
        ]);
    }

    public function restoreBackup(Request $request, RestoreDatabaseAction $action): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => 'required|string',
        ]);

        $action->execute($validated['file_name']);

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة قاعدة البيانات بنجاح من النسخة المحددة',
        ]);
    }
}
