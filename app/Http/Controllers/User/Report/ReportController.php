<?php

namespace App\Http\Controllers\User\Report;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\Request;
use App\Models\Score;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportController extends Controller
{
    public function dataAssessment(Request $request)
    {
        /**
         * mengambil data score dan di kelompokan sesuai id_contest, id participant , id_jury
         * dan mengambil baris
         * jumlah kolom score,
         * id participant
         * dan id jury
         * 
         */
        // $score = Score::groupBy('id_participant', 'id_jury', 'id_contest')->with(['lomba', 'peserta', 'user'])->selectRaw('sum(scores) as score,id_participant,id_jury,id_contest')->orderBy('score', 'DESC')->latest();
        /**
         * search data lomba dengan join table
         * jika terdapat permintaan post
         * 
         * dan jika tombol search btn di klik dan input text dengan nama
         * searchContestName dan searchEventName
         * maka akan mencari data yang mengandung nilai yang terdapat pada ke 2 input text tersebut
         * 
         * jika tombol exportPdf di klik
         * maka akan
         */
        $search = Score::join('contests', 'scores.id_contest', '=', 'contests.id')->join('events', 'contests.id_event', '=', 'events.id')
            ->join('users', 'scores.id_jury', '=', 'users.id')->join('participants', 'scores.id_participant', '=', 'participants.id')
            ->select('users.name as name_jury', DB::raw('SUM(scores.score) as total_score'), DB::raw('AVG(scores.score) as average'), 'contests.assessment_aspect as aspek', 'contests.name as name_contest', 'participants.name as name_participants', 'participants.id as id_participants', 'events.name as name_event',)->where('events.id_user', Auth::user()->id);

        if ($request->isMethod('POST')) {

            if ($request->has('searchBtn')) {
                $search->groupBy('scores.id_participant', 'scores.id_contest', 'scores.id_jury')->where('contests.name', 'like', '%' . request('searchContestName') . '%')->where('events.name', 'like', '%' . request('searchEventName') . '%')->latest('scores.created_at');
            }
            if ($request->has('exportPdf')) {
                $request->validate([
                    'searchContestName' => 'required',
                    'searchEventName' => 'required'
                ]);
                // query serach
                /**
                 * mengambil data score dan di kelompokan sesuai id_contest, id participant , id_jury
                 * dan mengambil baris
                 * jumlah kolom score,
                 * id participant
                 * 
                 */
                $querySearch = $search->where('contests.name', 'like', '%' . request('searchContestName') . '%')->where('events.name', 'like', '%' . request('searchEventName') . '%');
                $data = $querySearch->groupBy('scores.id_participant', 'scores.id_contest')->orderBy('average', 'DESC')->get();

                // rata rata tertinggi
                $maxAverage =  Score::join('contests', 'scores.id_contest', '=', 'contests.id')
                    ->join('events', 'contests.id_event', '=', 'events.id')
                    ->join('participants', 'scores.id_participant', '=', 'participants.id')
                    ->select(DB::raw('SUM(scores.score) as total_score'), DB::raw('AVG(scores.score) as average'), 'participants.name as name_participants',)
                    ->where('contests.name', 'like', '%' . request('searchContestName') . '%')
                    ->where('events.name', 'like', '%' . request('searchEventName') . '%')
                    ->where('events.id_user', Auth::user()->id)
                    ->groupBy('scores.id_participant', 'scores.id_contest')->orderBy('average', 'DESC')
                    ->limit(1)->firstOrFail();
                // rata rata terendah
                $minAverage = Score::join('contests', 'scores.id_contest', '=', 'contests.id')
                    ->join('events', 'contests.id_event', '=', 'events.id')
                    ->join('participants', 'scores.id_participant', '=', 'participants.id')
                    ->select(DB::raw('SUM(scores.score) as total_score'), DB::raw('AVG(scores.score) as average'), 'participants.name as name_participants',)
                    ->where('contests.name', 'like', '%' . request('searchContestName') . '%')
                    ->where('events.name', 'like', '%' . request('searchEventName') . '%')
                    ->where('events.id_user', Auth::user()->id)
                    ->groupBy('scores.id_participant', 'scores.id_contest')->orderBy('average', 'ASC')
                    ->limit(1)->firstOrFail();

                //mengirim data ke halaman export pdf assement dengan membawa data
                $pdf = PDF::loadView('user.report.pdf.export_pdf_assessment', [
                    'data' => $data,
                    'nameContest' => request('searchContestName'),
                    'nameEvent' => request('searchEventName'),
                    'maxAverage' => $maxAverage,
                    'minAverage' => $minAverage
                ])->setPaper('a4', 'landscape');
                // diberi nama dan di download
                return $pdf->download('pdf_export_assessment_' . request('searchEventName') . '_' . request('searchContestName') . '.pdf');
            }
            $search->groupBy('scores.id_participant', 'scores.id_contest', 'scores.id_jury')->latest('scores.created_at');
            $score = $search->paginate(10);
            return view('user.report.dataAssessment', [
                'data' => $score
            ]);
        }

        $search->groupBy('scores.id_participant', 'scores.id_contest', 'scores.id_jury')->latest('scores.created_at');
        $score = $search->paginate(10);
        return view('user.report.dataAssessment', [
            'data' => $score
        ]);
    }

    public function dataContest(Request $request)
    {
        // ambil data lomba di mana id user == id user login
        $search = Contest::join('events', 'contests.id_event', '=', 'events.id')->where('events.id_user', Auth::user()->id)
            ->select('contests.id as id_contest', 'contests.name as contest_name', 'contests.type as type_contest', 'contests.assessment_aspect as contest_aspect', 'events.name as event_name');
        // jika ada permintaan post
        if ($request->isMethod('POST')) {
            // jika tombol searchBtn di klik
            if ($request->has('searchBtn')) {
                $search->where('events.name', 'like', '%' . request('searchEventName') . '%');
            }
            // export pdf
            if ($request->has('exportPdf')) {
                //validasi form
                $request->validate([
                    'searchEventName' => 'required'
                ]);
                $data = $search->where('events.name', 'like', '%' . request('searchEventName') . '%')->get();
                $pdf = PDF::loadview('user.report.pdf.export_pdf_contest', [
                    'data' => $data,
                    'nameEvent' => request('searchEventName')
                ])->setPaper('a4', 'landscape');
                // diberi nama dan di download
                return $pdf->download('pdf_export_contest_' . request('searchEventName') . '.pdf');
            }

            // 
            $contest = $search->latest('contests.created_at')->paginate(10);
            return view('user.report.data_contest', [
                'contest' => $contest
            ]);
        }
        $contest = $search->latest('contests.created_at')->paginate(10);
        return view('user.report.data_contest', [
            'contest' => $contest
        ]);
    }

    public function dataEvent(Request $request)
    {
        // ambil data acara
        $event = Event::where('id_user', Auth::user()->id)->latest()->paginate(10);
        if ($request->isMethod('POST')) {

            if ($request->has('exportPdf')) {
                $data =  Event::where('id_user', Auth::user()->id)->get();
                $pdf = PDF::loadview('user.report.pdf.export_pdf_event', [
                    'data' => $data,
                ])->setPaper('a4', 'landscape');
                // diberi nama dan di download
                return $pdf->download('pdf_export_event.pdf');
            }
            $event = Event::where('id_user', Auth::user()->id)->latest()->paginate(10);
            return view('user.report.data_event', [
                'event' => $event
            ]);
        }
        return view('user.report.data_event', [
            'event' => $event
        ]);
    }

    public function dataParticipant(Request $request)
    {
        // ambil data peserta dengan join tabel events dan participants
        /** 
         * nama peserta,
         * jenis kelamin,
         * nomor telepon
         * alamat,
         * nama acara,
         * nama lomba
         * 
         * dimnana id user pada tabel event == id user login
         */
        $search = Participant::join('events', 'participants.id_event', '=', 'events.id')
            ->join('contests', 'participants.id_contest', '=', 'contests.id')
            ->select('participants.name as name_participant', 'participants.gender as gender_participant', 'participants.phone as phone_participant', 'participants.address as address_participant', 'events.name as name_event', 'contests.name as name_contest')
            ->where('events.id_user', Auth::user()->id);
        if ($request->isMethod('POST')) {
            //jika klik tombol search btn
            if ($request->has('searchBtn')) {
                $search->where('events.name', 'like', '%' . request('searchEventName') . '%')->where('contests.name', 'like', '%' . request('searchContestName') . '%');
            }
            // export pdf
            if ($request->has('exportPdf')) {
                $request->validate([
                    'searchContestName' => 'required',
                    'searchEventName' => 'required'
                ]);
                $data = $search->where('events.name', 'like', '%' . request('searchEventName') . '%')->where('contests.name', 'like', '%' . request('searchContestName') . '%')->get();
                $pdf = PDF::loadview('user.report.pdf.export_pdf_participant', [
                    'data' => $data,
                    'nameEvent' => request('searchEventName'),
                    'nameContest' => request('searchContestName'),
                ])->setPaper('a4', 'landscape');
                // diberi nama dan di download
                return $pdf->download('pdf_export_participants_' . request('searchEventName') . '_' . request('searchContestName') . '.pdf');
            }
            // menampilkan data yang dicari
            $participant = $search->latest('participants.created_at')->paginate(10);
            return view('user.report.data_participant', [
                'data' => $participant,

            ]);
        }
        $participant = $search->latest('participants.created_at')->paginate(10);
        return view('user.report.data_participant', [
            'data' => $participant
        ]);
    }
}
