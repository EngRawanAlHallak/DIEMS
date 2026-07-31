<?php

namespace App\Services;

use App\Actions\Admin\Events\ApproveEventRequestAction;
use App\Actions\Admin\Events\CancelEventRequestAction;
use App\Actions\Admin\Events\RejectEventRequestAction;
use App\Models\EventRequest;
use Illuminate\Validation\ValidationException;

class UpdateEventStatusService
{
    public function __construct(
        private CancelEventRequestAction $cancel,
        private RejectEventRequestAction $reject,
        private ApproveEventRequestAction $approve,
    ) {}

    public function updateStatus(int $requestId, string $status)
    {
        if($status === 'approved') {
            $eventRequest = EventRequest::findOrFail($requestId);
            if($eventRequest->hall_id === null)
                throw ValidationException::withMessages([
                    'choose a valid hall first',
                ]);

            $this->approve->execute($eventRequest);
        }

        elseif ($status === 'rejected') {
            $this->reject->execute($requestId);
        }

        elseif ($status === 'cancelled') {
            $this->cancel->execute($requestId);
        }

        else {
            throw ValidationException::withMessages([
                'enter a valid status [approved, rejected or cancelled]',
            ]);
        }
    }
}
