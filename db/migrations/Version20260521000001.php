<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default users, pipeline stages, tags and sample data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role`) VALUES
            ('Admin', 'User', 'admin@crm.local', '\$2y\$12\$Hu9DQnQFkQ43uJQDJ.pAPue5pbEg774NBHPr719tVB9A2JWwdSU5W', 'admin'),
            ('John', 'Manager', 'manager@crm.local', '\$2y\$12\$Hu9DQnQFkQ43uJQDJ.pAPue5pbEg774NBHPr719tVB9A2JWwdSU5W', 'manager'),
            ('Jane', 'Agent', 'agent@crm.local', '\$2y\$12\$Hu9DQnQFkQ43uJQDJ.pAPue5pbEg774NBHPr719tVB9A2JWwdSU5W', 'agent')
        ");

        $this->addSql("INSERT INTO `pipeline_stages` (`name`, `slug`, `color`, `sort_order`, `is_won`, `is_lost`) VALUES
            ('New Lead',      'new',         '#6c757d', 1, 0, 0),
            ('Qualified',     'qualified',   '#0d6efd', 2, 0, 0),
            ('Proposal Sent', 'proposal',    '#fd7e14', 3, 0, 0),
            ('Negotiation',   'negotiation', '#6f42c1', 4, 0, 0),
            ('Won',           'won',         '#198754', 5, 1, 0),
            ('Lost',          'lost',        '#dc3545', 6, 0, 1)
        ");

        $this->addSql("INSERT INTO `tags` (`name`, `color`) VALUES
            ('VIP',        '#ffc107'),
            ('Hot Lead',   '#dc3545'),
            ('Cold Lead',  '#0dcaf0'),
            ('Newsletter', '#0d6efd'),
            ('Partner',    '#6f42c1')
        ");

        $this->addSql("INSERT INTO `companies` (`name`, `email`, `phone`, `website`, `industry`, `employees`, `city`, `country`, `owner_id`) VALUES
            ('Acme Corp', 'info@acme.com',    '+1-555-0100', 'https://acme.com',    'Technology', 250, 'New York', 'USA', 1),
            ('Beta Ltd',  'hello@betaltd.com', '+1-555-0200', 'https://betaltd.com', 'Finance',    50,  'London',   'UK',  2)
        ");

        $this->addSql("INSERT INTO `contacts` (`first_name`, `last_name`, `email`, `phone`, `company_id`, `job_title`, `status`, `source`, `city`, `country`, `owner_id`) VALUES
            ('Alice', 'Smith', 'alice@acme.com',    '+1-555-1001', 1,    'CEO',        'customer', 'referral', 'New York', 'USA',     1),
            ('Bob',   'Jones', 'bob@betaltd.com',   '+1-555-1002', 2,    'CFO',        'prospect', 'web',      'London',   'UK',      2),
            ('Carol', 'Brown', 'carol@example.com', '+1-555-1003', NULL, 'Freelancer', 'lead',     'email',    'Berlin',   'Germany', 3)
        ");

        $this->addSql("INSERT INTO `deals` (`title`, `contact_id`, `company_id`, `owner_id`, `stage`, `value`, `currency`, `probability`, `expected_close_date`) VALUES
            ('Acme Enterprise License', 1, 1,    1, 'negotiation', 25000.00, 'USD', 75, '2026-06-30'),
            ('Beta Analytics Suite',   2, 2,    2, 'proposal',    12000.00, 'USD', 50, '2026-07-15'),
            ('Carol Consulting',       3, NULL, 3, 'qualified',    3500.00, 'USD', 40, '2026-08-01')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `deals` WHERE `title` IN ('Acme Enterprise License', 'Beta Analytics Suite', 'Carol Consulting')");
        $this->addSql("DELETE FROM `contacts` WHERE `email` IN ('alice@acme.com', 'bob@betaltd.com', 'carol@example.com')");
        $this->addSql("DELETE FROM `companies` WHERE `email` IN ('info@acme.com', 'hello@betaltd.com')");
        $this->addSql("DELETE FROM `tags` WHERE `name` IN ('VIP', 'Hot Lead', 'Cold Lead', 'Newsletter', 'Partner')");
        $this->addSql("DELETE FROM `pipeline_stages` WHERE `slug` IN ('new', 'qualified', 'proposal', 'negotiation', 'won', 'lost')");
        $this->addSql("DELETE FROM `users` WHERE `email` IN ('admin@crm.local', 'manager@crm.local', 'agent@crm.local')");
    }
}
