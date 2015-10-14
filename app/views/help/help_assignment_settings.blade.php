@extends('templates.master')

@section('title') 
Help: assignment settings
@stop 

@section('sub_title') 
@stop

@section('content')
@parent

<h3>Title</h3>
A meaningful title of this assignment. It is shown in various places to distinguish this assignment from others of the same class.


<h3>Visibility</h3>
<p>
<strong>Option 1:</strong> The assignment is invisible to tutors and students. This is intended furing the draft phase. It allows you
to prepare an assignment without immediately publishing it. It is recommended to use this setting initially and then choose one of the
other ones once you finished writing the questions. Once an assignment is visible, it is open for submissions by the students. 
</p>

<strong>Option 2:</strong> The assignment is visible and the questions are displayed. This also implies that the assignment is open for 
submissions (assuming it is not overdue and submissions are blocked - see below).   
</p>

<strong>Option 3+4:</strong> Like above. But as soon as the marking process has started, the solutions (and possibly marking scheme) are
displayed to everyone as well. Note: whenever a user (student or tutor) is peer reviewing or marking an assignment question, the solution
and marking scheme for this particular question are displayed regardless of this setting.
</p><p>
Option 4 is the recommended setting for an assignment once it has been posted. 
</p>


<h3>Due date</h3>
<p>
This is the date the answers by the students are due. Late submissions after this date might be accepted (see next setting below). If an
answer was submitted late, it will be clearly marked as such during the marking process when you assign the final mark for a particular
answer so you can down-grade a late submission there. 
</p><p>
Once the marking process has started, this date cannot be changed anymore.
<p>

<h3>Late submission policy</h3>
<p>
<strong>Option 1:</strong> The due date is considered a hard deadline and after the due date no further answers can be submitted by the 
students.
</p>

<p>
<strong>Option 2:</strong> Late submissions are accepted until the marking process has started. This way late submissions can partake 
in the marking process the normal way and also be peer reviewed.
</p>

<p>
<strong>Option 3+4:</strong> Late submissions are accepted indefinitely. However, after the marking process has been started it is no longer
possible to add the answers to the peer review process. Thus, answers that were submitted after the marking process has started will be 
distributed to the tutors or the lecturer only. 
</p>


<h3>Autostart marking</h3>
<p>
Normally the marking process is started manually in the assignment's control panel. If you want to start the marking phase at a pre-determined
point in time, you can enter it here.   
</p><p>
Note: platypus uses a cron-job to regularly check for assignments that need their marking process started. Thus, the actual start of the marking
process might be delayed depending of the frequency this cron job is run on your installation.
<p>

<h3>Students guess their marks</h3>
<p>
It is possible to ask students to predict their mark when they submit an answer. This prediction has no effect to the inner workings of platypus.
However, the prediction will be shown to you when you set the final mark for an answer and it will be included into the final result table
you can download.
</p>

<h3>Number of peers</h3>
<p>
Platypus is built around the concept that students anonymously peer-review each other's answers. When the marking process is started, each answer
is distributed to a number of students at random for review. Here you can select how many times each answer is to be peer-reviewed. You can set 
this number to zero to deactivate the peer review functionality completely. Then only tutors and lecturers will do the marking.
</p>
<p>
Note: only students who submitted an answer to a particular question (or assignment - see next setting below) will be asked to review answers 
for that very question (or assignment).
</p>

<h3>Shuffle mode</h3>
<p>
<strong>Option 1:</strong> When shuffling answers for the peer reviewing process, each question is treated individually. Thus, when a student submits
answers to several questions, each answer is reviewed by a different set of peer reviewers. Note: sub-questions will always be kept together. Thus,
you can create multi part questions that will always be reviewed as a whole by a single reviewer.
</p>
<p>
<strong>Option 2:</strong> When shuffling for the peer reviewing process, assignment sheets are sent to reviewers as a whole. That means a reviewer
always gets to mark an entire assignment sheet. This option is useful if you have interconnected questions where answers might cross-reference answers
of other questions.
</p><p>
Note: if only some of your questions cross-reference each other, it is recommended to create a master-question with sub-questions. Answers to the same
master-question will always be kept together and sent to a reviewer as a whole. 
</p>

<h3>Peer review due date</h3>
The date you want the student to finish the peer reviews by. This date is informative only and is not enforced in any way. Peer reviews do not have a time
limit imposed on them. However, when you assign a final mark for an answer, all pending peer review requests are cancelled. Thus, the peer review
process for an individual answer runs until you assign a final mark to that very answer. 

<h3>Tutor marking mode</h3>
<p>
Here you can select whether tutors have to mark student answers or not. If tutor marking is activated, review requests for each answer are created and
sent to the tutors.
</p>
<p>
By default, the work load is distributed evenly amongst all tutors that are part of your class. However, you can select which tutor is responsible 
for which question(s) on the assignment's Manage tutors page if required.
</p> 

<h3>Tutor marks due date</h3>
Same as peer reviews due date, but for tutors.

<h3>Mark display mode</h3>
<p>
This is the setting for how the marks and review results are released to the students.
</p>
<p>
<strong>Option 1:</strong> No marks and student results are displayed.
</p>
<p>
<strong>Option 2:</strong> As soon as a student has completed all peer-review tasks, the student can see the received reviews. This acts as an incentive
for students to finish their review tasks early. Only the reviews are shown, but not the official final marks. 
Note: the students will not only see peer-reviews, but also reviews written by tutors or the lecturer.
</p>
<p>
<strong>Option 3:</strong> Like option 2, but also the final marks for the questions are shown where available. This is the recommended setting if
you want to manually double check the final marks of assignments before they are released. 
</p>
<p>
<strong>Option 4:</strong> Like option 3, but also the final mark for the assignment is shown where available. Note: once all answers a student submitted
have received a final mark, the aggregated mark for the whole assignment is calculated and displayed to the student. This is the recommended setting if
you trust the automatic calculation of the aggregated mark.
</p>
<p>
<strong>Option 5-7:</strong> Like option 2-4, but all available results are shown regardless of whether the student finished the peer-review tasks.
</p>

<h3>Group work mode</h3>
<p>
<strong>Option 1:</strong> Group work is deactivated and all students have to submit individual solutions.
</p>
<p>
<strong>Option 2:</strong> Group work is enabled, but and all students still have to submit individual solutions. In this mode, since each student is 
responsible for their own answers, the only real consequence of student groups is when peer reviews are assigned. Platypus ensures that solutions are
not reviewed by a student who is in the same group as the student who submitted them.
</p>
<p>
<strong>Option 3:</strong> Group work is enabled and only one solution per group is needed. For each question, the first solution that is submitted is
considered the group solution and no further answers for that particular question can be submitted by other members of the group. Other members of the
group can see the group submission but only the original author can retract and change it.
</p><p>
When peer review tasks are assigned, each review task gets assigned to all members of a student group and again, once the first review is submitted, that
review is considered to be the one review by the group and the review tasks for other group members for this answer are cancelled.
</p><p>
When the final mark for an answer is assigned, that mark is given to all members of the group. However, it is possible to manually adjust marks for 
individual members of the group in the assignment's result editor if required.	 
</p>

<p>
Note: if group work is enabled, students can only submit solutions once they are part of a student group. This rule has one exception: if students can
self-assign groups (see next setting below) AND the minimum group size is 1, then a student who is not yet in a group can submit solutions. Upon submission
of a solution such a student is placed into a group on his/her own. 
</p>

<p>
WARNING: if you deactivate group work (switch from option 2 or 3 to option 1), all student groups are deleted immediately. Thus, id group work is re-enabled,
the student groups have to be formed anew. 
</p>


<h3>Group selection mode</h3>
<p>
Here you can decide whether students can form student groups themselves or whether all groups have to be assigned manually by the lecturer. It is recommended
to allow students to self-assign groups.
</p><p>
The self-assignment process works as follows. Students can suggest a group based on email addresses or student ids of their proposed group mates. Once a group
is suggested, this group suggestion is shown to all proposed members of the group to accept or decline. If a student declines a group proposal, the proposal
is deleted. Once all students have accepted the proposal, the group is formed. There is no limit on the number of group proposals a student can create. However,
once the first proposal is successful, that group is formed; all other conflicting proposals affecting its members are deleted automatically. 
</p><p>
Once a group is formed, only the lecturer can change or dissolve the group.
</p>

<h3>Minimum/maximum group size</h3>
These limits on the group size are only enforced when students suggest groups themselves (see above setting). As lecturer you can always form groups of any size.

@stop


