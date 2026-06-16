<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('tickets.view'), 403);
        $tickets = SupportTicket::with(['user', 'assignee'])->when($request->status, fn ($q, $v) => $q->where('status', $v))->when($request->priority, fn ($q, $v) => $q->where('priority', $v))->when($request->search, fn ($q, $v) => $q->where(fn ($x) => $x->where('ticket_number', 'like', "%{$v}%")->orWhere('subject', 'like', "%{$v}%")))->latest()->paginate(25)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        abort_unless($request->user()->can('tickets.view'), 403);

        return view('admin.tickets.show', ['ticket' => $ticket->load(['user', 'assignee', 'replies.user', 'replies.attachments']), 'agents' => User::permission('tickets.reply')->orderBy('name')->get()]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        abort_unless($request->user()->can('tickets.update'), 403);
        $data = $request->validate(['status' => 'required|in:open,in_progress,resolved,closed,reopened', 'priority' => 'required|in:low,medium,high,critical', 'assigned_to' => 'nullable|exists:users,id']);
        $data['resolved_at'] = $data['status'] === 'resolved' ? now() : null;
        $data['closed_at'] = $data['status'] === 'closed' ? now() : null;
        $ticket->update($data);

        return back()->with('success', 'Ticket updated.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($request->user()->can('tickets.reply'), 403);
        $data = $request->validate(['message' => 'required|string|max:10000', 'is_internal' => 'nullable|boolean', 'attachment' => 'nullable|file|max:10240']);
        $reply = $ticket->replies()->create(['user_id' => $request->user()->id, 'message' => $data['message'], 'is_internal' => $request->boolean('is_internal')]);
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $reply->attachments()->create(['ticket_id' => $ticket->id, 'uploaded_by' => $request->user()->id, 'original_name' => $file->getClientOriginalName(), 'path' => $file->store('tickets', 'public'), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
        }

        return back()->with('success', 'Reply added.');
    }
}
