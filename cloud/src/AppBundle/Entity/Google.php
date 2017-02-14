<?php

namespace AppBundle\Entity;

/**
 * Google
 */
class Google
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $status;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userid
     *
     * @param integer $userid
     *
     * @return Google
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid
     *
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Google
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Set status
     *
     * @param string $status
     *
     * @return Google
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

}
