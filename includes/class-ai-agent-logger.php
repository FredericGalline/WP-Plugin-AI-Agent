<?php

/**
 * Classe de journalisation personnalisée pour WP Plugin AI Agent
 * 
 * Cette classe permet de journaliser les erreurs, avertissements et informations
 * dans un fichier local au plugin, rendant le débogage plus facile et plus propre.
 * 
 * @package WP_Plugin_AI_Agent
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class AI_Agent_Logger
{
    /**
     * Le chemin vers le fichier de log
     * 
     * @var string
     */
    private $log_file;

    /**
     * Si la journalisation est activée
     * 
     * @var bool
     */
    private $enabled;

    /**
     * Niveau de journalisation (1=Erreur, 2=Avertissement, 3=Info, 4=Débogage)
     * 
     * @var int
     */
    private $level;

    /**
     * Constructeur
     * 
     * @param string $log_file Chemin vers le fichier de log
     * @param bool $enabled Si la journalisation est activée
     * @param int $level Niveau de journalisation
     */
    public function __construct($log_file = null, $enabled = true, $level = 4)
    {
        // Si aucun fichier de log n'est spécifié, utiliser le fichier par défaut
        if (is_null($log_file)) {
            $log_file = WP_PLUGIN_AI_AGENT_PATH . 'logs/ai-agent.log';
        }

        $this->log_file = $log_file;
        $this->enabled = $enabled;
        $this->level = $level;

        // Créer le répertoire logs s'il n'existe pas
        $logs_dir = dirname($this->log_file);
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);

            // Créer un fichier .htaccess pour protéger le répertoire
            $htaccess_file = $logs_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, "# Deny access to all files in this directory\nOrder allow,deny\nDeny from all");
            }

            // Créer un fichier index.php vide pour empêcher la navigation
            $index_file = $logs_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.");
            }
        }
    }

    /**
     * Journalise un message d'erreur
     * 
     * @param string $message Le message d'erreur
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function error($message, $context = [])
    {
        if ($this->enabled && $this->level >= 1) {
            $this->log('ERROR', $message, $context);
        }
    }

    /**
     * Journalise un message d'avertissement
     * 
     * @param string $message Le message d'avertissement
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function warning($message, $context = [])
    {
        if ($this->enabled && $this->level >= 2) {
            $this->log('WARNING', $message, $context);
        }
    }

    /**
     * Journalise un message d'information
     * 
     * @param string $message Le message d'information
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function info($message, $context = [])
    {
        if ($this->enabled && $this->level >= 3) {
            $this->log('INFO', $message, $context);
        }
    }

    /**
     * Journalise un message de débogage
     * 
     * @param string $message Le message de débogage
     * @param array $context Contexte supplémentaire
     * @return void
     */
    public function debug($message, $context = [])
    {
        if ($this->enabled && $this->level >= 4) {
            $this->log('DEBUG', $message, $context);
        }
    }

    /**
     * Journalise un message
     * 
     * @param string $level Niveau de journalisation
     * @param string $message Le message
     * @param array $context Contexte supplémentaire
     * @return void
     */
    private function log($level, $message, $context = [])
    {
        // Préparer le message avec la date et le niveau
        $date_time = new \DateTime();
        $log_message = sprintf(
            "[%s] [%s] %s",
            $date_time->format('Y-m-d H:i:s'),
            $level,
            $message
        );

        // Ajouter le contexte si nécessaire
        if (!empty($context)) {
            $log_message .= " - Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // Ajouter un saut de ligne
        $log_message .= PHP_EOL;

        // Écrire dans le fichier de log
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    /**
     * Efface le fichier de log
     * 
     * @return void
     */
    public function clear()
    {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }

    /**
     * Retourne le contenu du fichier de log
     * 
     * @param int $lines Nombre de lignes à retourner (0 = toutes)
     * @return string
     */
    public function get_log_content($lines = 0)
    {
        if (!file_exists($this->log_file)) {
            return '';
        }

        $content = file_get_contents($this->log_file);

        if ($lines > 0) {
            $log_array = explode(PHP_EOL, $content);
            $log_array = array_filter($log_array);
            $log_array = array_slice($log_array, -$lines);
            $content = implode(PHP_EOL, $log_array);
        }

        return $content;
    }
}
