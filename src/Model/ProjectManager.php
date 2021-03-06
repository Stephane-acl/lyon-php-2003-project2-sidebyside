<?php


namespace App\Model;

class ProjectManager extends AbstractManager
{

    const TABLE = 'projects';

    /**
     *  Initializes this class.
     */

    public function __construct()
    {
        parent::__construct(self::TABLE);
    }

    /**
     * @param string $keyword
     * @return array
     */
    public function selectByWord(string $keyword): array
    {
        $query = "SELECT p.id, p.title, p.description, p.zip_code, p.banner_image, p.created_at, p.project_owner_id,
                    u.first_name,  u.last_name, u.profil_picture
                    FROM projects p 
                    JOIN users u ON p.project_owner_id=u.id  
                    WHERE title LIKE :keyword 
                    OR p.zip_code LIKE :keyword
                    OR u.first_name LIKE :keyword
                    OR u.last_name LIKE :keyword";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':keyword', "%$keyword%");
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @param string $userId
     * @return array
     */
    public function selectIWorkOnByUserId(string $userId): array
    {
        $query = "SELECT p.title, p.description, p.zip_code, p.banner_image, p.id, p.project_owner_id, phc.user_id
                    FROM projects p 
                    LEFT JOIN project_has_collaborators phc
                    ON phc.project_id=p.id
                    WHERE phc.user_id=:id
                 ";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $userId);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @param string $userId
     * @return array
     */
    public function selectByOwnerId(string $userId): array
    {
        $query = "SELECT p.title, p.description, p.zip_code, p.banner_image, p.id, p.project_owner_id
                    FROM projects p
                    WHERE p.project_owner_id=:id";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $userId);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @param array $project
     * @param int $sessionId
     * @return int
     */
    public function insert(array $project, int $sessionId): int
    {
        // prepared request

        $statement = $this->pdo->prepare(
            "INSERT INTO $this->table
(`title`, `description`, `deadline`, `zip_code`, `project_owner_id`, `category_id`, `banner_image`, `created_at`)
                        VALUES 
 (:title, :description, :deadline, :zip_code, :project_owner_id , :category_id, :banner_image, NOW())"
        );
        $statement->bindValue('title', $project['title'], \PDO::PARAM_STR);
        $statement->bindValue('description', $project['description'], \PDO::PARAM_STR);
        $statement->bindValue('deadline', $project['deadline']);
        $statement->bindValue('zip_code', $project['zip_code'], \PDO::PARAM_STR);
        $statement->bindValue('project_owner_id', $sessionId, \PDO::PARAM_INT);
        $statement->bindValue('category_id', $project['category_id'], \PDO::PARAM_INT);
        $statement->bindValue('banner_image', $project['banner_image']);

        if ($statement->execute()) {
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function selectAllExceptCurrent(int $id)
    {
        // Selectionne tous les projets sauf le current
        $statement = $this->pdo->prepare("SELECT * FROM $this->table WHERE id!=:id");
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function selectLastProject()
    {
        return $this->pdo->query('SELECT id FROM ' . $this->table . ' ORDER BY id DESC ')->fetch();
    }

    public function askProjectOwner($id)
    {
        $statement = $this->pdo->prepare('SELECT u.profil_picture, u.id FROM users as u
                JOIN projects as p ON u.id = p.project_owner_id WHERE p.project_owner_id=:id');
        $statement->bindValue('id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    // REQUEST TO UPDATE PROJECT INFOS

    /**
     * @param array $project
     * @return bool
     */
    public function update(array $project)
    {
        $statement = $this->pdo->prepare("UPDATE " . self::TABLE . " SET 
        `title` = :title, 
        `description` = :description,
        `zip_code` = :zip_code,
        `plan`= :plan,
        `team_description` = :team_description, 
        `deadline`= :deadline,
        `category_id` = :category_id
         WHERE id=:id ");
        $statement->bindValue('id', $project['id'], \PDO::PARAM_INT);
        $statement->bindValue('title', $project['title'], \PDO::PARAM_STR);
        $statement->bindValue('description', $project['description'], \PDO::PARAM_STR);
        $statement->bindValue('zip_code', $project['zip_code'], \PDO::PARAM_STR);
        $statement->bindValue('plan', $project['plan'], \PDO::PARAM_STR);
        $statement->bindValue('team_description', $project['team_description'], \PDO::PARAM_STR);
        $statement->bindValue('deadline', $project['deadline']);
        $statement->bindValue('category_id', $project['category_id'], \PDO::PARAM_INT);

        return $statement->execute();
    }

    // REQUEST TO INSERT BANNER IMAGE IN PROJECT
    public function updateProjectImg(array $path, int $id)
    {
        if (isset($path['banner_image'])) {
            $update = $this->pdo->prepare("UPDATE projects AS p 
                                                        SET p.banner_image = :banner_image 
                                                        WHERE p.id = :id");
            $update->bindParam('banner_image', $path["banner_image"], \PDO::PARAM_STR);
            $update->bindValue('id', $id, \PDO::PARAM_INT);
            $update->execute();
        }
    }
}
