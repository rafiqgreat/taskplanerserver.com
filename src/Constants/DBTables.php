<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 8:43 PM
 */
namespace Constants;


class DBTables
{
    // Table Names
    private $userAuths              = 'ptf_user_auth';
    private $userDetails            = 'ptf_user_details';
    private $userSecrets            = 'ptf_user_secrets';
    private $validationCode         = 'ptf_user_validation_codes';
    private $projects               = 'cor_project_details';
    private $projectMembers         = 'cor_project_members';
    private $tasks                  = 'cor_project_tasks';
    private $chats                  = 'cor_chats';

    private $userDetailsVW          = 'ptf_user_details_vw';
    private $projectsMembersVW      = 'cor_projects_members_vw';
    private $projectTaskVW          = 'cor_project_tasks_vw';
    private $assignedProjectsVW     = 'cor_user_assigned_project_vw';
    private $assignedTasksVW        = 'cor_user_assigned_tasks_vw';
    private $notifications          = 'cor_notifications';

    /**$notifications
     * @return string
     */
    public function getUserAuths()
    {
        return $this->userAuths;
    }

    /**
     * @return string
     */
    public function getUserDetails()
    {
        return $this->userDetails;
    }

    /**
     * @return string
     */
    public function getUserSecrets()
    {
        return $this->userSecrets;
    }

    /**
     * @return string
     */
    public function getValidationCode()
    {
        return $this->validationCode;
    }

    /**
     * @return string
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return string
     */
    public function getProjectMembersTBL()
    {
        return $this->projectMembers;
    }

    /**
     * @return string
     */
    public function getUserDetailsVW()
    {
        return $this->userDetailsVW;
    }

    /**
     * @return string
     */
    public function getProjectsMembersVW()
    {
        return $this->projectsMembersVW;
    }

    /**
     * @return string
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @return string
     */
    public function getChats()
    {
        return $this->chats;
    }

    /**
     * @return string
     */
    public function getProjectMembers()
    {
        return $this->projectMembers;
    }

    /**
     * @return string
     */
    public function getProjectTasks()
    {
        return $this->tasks;
    }

    /**
     * @return string
     */
    public function getProjectTaskVW()
    {
        return $this->projectTaskVW;
    }

    /**
     * @return string
     */
    public function getAssignedProjectsVW()
    {
        return $this->assignedProjectsVW;
    }

    /**
     * @return string
     */
    public function getAssignedTasksVW()
    {
        return $this->assignedTasksVW;
    }
    
    /**
     * @return string
     */
    public function getNotifications()
    {
        return $this->notifications;
    }
    
    
    
}