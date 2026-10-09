<?php

namespace Database\Seeders;

use App\Models\PlanRecord;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $marshallRole = Role::firstOrCreate(['name' => 'Marshall']);
        $executorRole = Role::firstOrCreate(['name' => 'Executor']);

        // Create sample Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@plantrack.test'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->syncRoles($adminRole);

        // Create sample Executors
        $executor1 = User::firstOrCreate(
            ['email' => 'executor@plantrack.test'],
            [
                'name' => 'John Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor1->syncRoles($executorRole);

        // Add two new Executors
        $executor2 = User::firstOrCreate(
            ['email' => 'executor2@plantrack.test'],
            [
                'name' => 'Sarah Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor2->syncRoles($executorRole);

        $executor3 = User::firstOrCreate(
            ['email' => 'executor3@plantrack.test'],
            [
                'name' => 'Marcus Executor',
                'password' => bcrypt('password'),
            ]
        );
        $executor3->syncRoles($executorRole);

        // Create sample Marshall
        $marshall = User::firstOrCreate(
            ['email' => 'marshall@plantrack.test'],
            [
                'name' => 'Mary Marshall',
                'password' => bcrypt('password'),
            ]
        );
        $marshall->syncRoles($marshallRole);

        // Create sample Projects
        $project1 = Project::firstOrCreate(
            ['name' => 'Operation Hyperion: Core Infrastructure'],
            ['status' => 'Active']
        );
        $project2 = Project::firstOrCreate(
            ['name' => 'Project Aegis: Defensive Cybermesh'],
            ['status' => 'Active']
        );
        Project::firstOrCreate(
            ['name' => 'Project Chimera: Legacy Systems'],
            ['status' => 'Deactive']
        );
        $project4 = Project::firstOrCreate(
            ['name' => 'Project Titan: Zero Trust Network Architecture'],
            ['status' => 'Active']
        );
        $project5 = Project::firstOrCreate(
            ['name' => 'Project Nexus: Automated Observability Pipeline'],
            ['status' => 'Active']
        );

        // =========================================================================
        // EXECUTOR 1 (John Executor) - 3 Plan Records & Components
        // =========================================================================

        // Plan 1.1: Review
        $plan1_1 = PlanRecord::firstOrCreate(
            ['title' => 'Q4 Core Cloud Migration & Security Upgrade'],
            [
                'description' => 'Migrate critical legacy databases and worker nodes to resilient cloud architecture with ISO 27001 compliance standards.',
                'executor_id' => $executor1->id,
                'project_id' => $project1->id,
                'status' => 'Review',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(15),
            ]
        );

        $t1_1_1 = $plan1_1->targetObjectives()->firstOrCreate(
            ['description' => 'Migrate 10 MySQL clusters to Cloud Managed instances with zero data loss.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '10 Clusters',
                'priority' => 'high',
                'status' => 'Hit',
            ]
        );

        $t1_1_2 = $plan1_1->targetObjectives()->firstOrCreate(
            ['description' => 'Achieve 99.99% uptime during DNS cutover.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '99.99%',
                'priority' => 'critical',
                'status' => 'Hit',
            ]
        );

        $plan1_1->targetObjectives()->firstOrCreate(
            ['description' => 'Execute legacy hardware decommissioning protocol.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '3 Data centers',
                'priority' => 'low',
                'status' => null,
            ]
        );

        $plan1_1->resources()->firstOrCreate(
            ['description' => 'Dedicated Cloud Infrastructure Architects and DevOps Engineers.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_1_1->id,
                'quantity' => '3 Specialists',
                'target_date' => now()->subDays(14),
                'date_available' => now()->subDays(15),
                'status' => 'Hit',
            ]
        );

        $plan1_1->resources()->firstOrCreate(
            ['description' => 'Cloud Provider Migration Credits and Support Contract.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '$25,000 USD',
                'target_date' => now()->subDays(10),
                'date_available' => now()->subDays(12),
                'status' => 'Hit',
            ]
        );

        $r1_1_1 = $plan1_1->riskManagements()->firstOrCreate(
            ['risk' => 'Network bandwidth throttling during terabyte data transfer.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Provision direct 10Gbps dedicated interconnect with compression pipeline.',
                'status' => 'Hit',
            ]
        );

        $plan1_1->riskManagements()->firstOrCreate(
            ['risk' => 'Unexpected third-party API incompatibility on new cloud runtime.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Run parallel staging validation harness for 72 hours before live traffic switch.',
                'status' => null,
            ]
        );

        $plan1_1->budgets()->firstOrCreate(
            ['description' => 'Cloud Database Migration Tooling & Replication Licensing'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_1_1->id,
                'quantity' => '8500',
                'unit' => 'USD',
                'actual' => '8200',
                'status' => 'Hit',
            ]
        );

        $plan1_1->budgets()->firstOrCreate(
            ['description' => 'External Infrastructure Security Audit Retainer'],
            [
                'user_id' => $marshall->id,
                'quantity' => '12000',
                'unit' => 'USD',
                'actual' => '12000',
                'status' => 'Hit',
            ]
        );

        $t1_1_1->comments()->firstOrCreate(
            ['body' => 'All 10 databases successfully migrated and checksums verified.'],
            ['user_id' => $executor1->id]
        );

        $t1_1_1->comments()->firstOrCreate(
            ['body' => 'Verified checksum reports. Excellent execution speed.'],
            ['user_id' => $marshall->id]
        );

        $r1_1_1->comments()->firstOrCreate(
            ['body' => 'Bandwidth monitor peaked at 8.2Gbps with zero dropouts. Mitigation was effective.'],
            ['user_id' => $marshall->id]
        );

        // Plan 1.2: Open
        $plan1_2 = PlanRecord::firstOrCreate(
            ['title' => 'Kubernetes Cluster Hardening & Service Mesh Integration'],
            [
                'description' => 'Deploy service mesh sidecars across all microservices, establish Pod Security Standards, and integrate mutual TLS for cluster traffic.',
                'executor_id' => $executor1->id,
                'project_id' => $project4->id,
                'status' => 'Open',
                'start_date' => now()->subDays(6),
                'end_date' => now()->addDays(24),
            ]
        );

        $t1_2_1 = $plan1_2->targetObjectives()->firstOrCreate(
            ['description' => 'Deploy Istio service mesh across 24 Kubernetes worker clusters.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '24 Clusters',
                'priority' => 'critical',
                'actual' => '24',
                'unit' => 'Clusters',
                'status' => 'Hit',
            ]
        );

        $plan1_2->targetObjectives()->firstOrCreate(
            ['description' => 'Enforce Pod Security Standards (Restricted) on all production namespaces.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '100%',
                'priority' => 'high',
                'status' => null,
            ]
        );

        $plan1_2->resources()->firstOrCreate(
            ['description' => 'Certified Kubernetes Security Specialists (CKS).'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_2_1->id,
                'quantity' => '2 Specialists',
                'target_date' => now()->addDays(5),
                'date_available' => now()->subDays(3),
                'status' => 'Hit',
            ]
        );

        $plan1_2->resources()->firstOrCreate(
            ['description' => 'Dedicated staging cluster compute allocation for canary rollouts.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '1 Cluster',
                'target_date' => now()->addDays(10),
                'status' => null,
            ]
        );

        $plan1_2->riskManagements()->firstOrCreate(
            ['risk' => 'Service mesh proxy overhead increasing P99 latency beyond 5ms.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Optimize Envoy memory cache and tune connection keepalive timers.',
                'status' => 'Hit',
            ]
        );

        $plan1_2->riskManagements()->firstOrCreate(
            ['risk' => 'Pod Security Admission blocking legacy daemonsets upon cluster upgrade.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Perform pre-upgrade admission dry-run scans and update helm templates.',
                'status' => null,
            ]
        );

        $plan1_2->budgets()->firstOrCreate(
            ['description' => 'Enterprise Service Mesh Support & Tooling Subscription'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_2_1->id,
                'quantity' => '14000',
                'unit' => 'USD',
                'actual' => '13800',
                'status' => 'Hit',
            ]
        );

        $plan1_2->budgets()->firstOrCreate(
            ['description' => 'Canary Ingress Load Balancer Compute Buffer'],
            [
                'user_id' => $marshall->id,
                'quantity' => '5200',
                'unit' => 'USD',
                'actual' => null,
                'status' => null,
            ]
        );

        $t1_2_1->comments()->firstOrCreate(
            ['body' => 'Istio control plane successfully rolled out to all 24 clusters with mTLS STRICT.'],
            ['user_id' => $executor1->id]
        );

        // Plan 1.3: Close
        $plan1_3 = PlanRecord::firstOrCreate(
            ['title' => 'Legacy Monolith Decommissioning & Data Archival'],
            [
                'description' => 'Securely archive legacy relational records to cold storage, decommission physical server racks, and revoke decommissioned access tokens.',
                'executor_id' => $executor1->id,
                'project_id' => $project1->id,
                'status' => 'Close',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(10),
            ]
        );

        $t1_3_1 = $plan1_3->targetObjectives()->firstOrCreate(
            ['description' => 'Safely archive 15TB of cold audit logs to encrypted Glacier Deep Archive.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '15',
                'unit' => 'TB',
                'priority' => 'critical',
                'actual' => '15',
                'status' => 'Hit',
            ]
        );

        $t1_3_2 = $plan1_3->targetObjectives()->firstOrCreate(
            ['description' => 'Power down and securely wipe 18 legacy bare-metal server racks.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '18',
                'unit' => 'Racks',
                'priority' => 'high',
                'actual' => '18',
                'status' => 'Hit',
            ]
        );

        $plan1_3->resources()->firstOrCreate(
            ['description' => 'Certified Data Sanitization Hardware and On-site technician team.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_3_2->id,
                'quantity' => '1 Tech Team',
                'target_date' => now()->subDays(30),
                'date_available' => now()->subDays(32),
                'status' => 'Hit',
            ]
        );

        $plan1_3->resources()->firstOrCreate(
            ['description' => 'High-throughput cloud direct connect pipe for bulk archive transfer.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_3_1->id,
                'quantity' => '1 Direct Link',
                'target_date' => now()->subDays(45),
                'date_available' => now()->subDays(48),
                'status' => 'Hit',
            ]
        );

        $plan1_3->riskManagements()->firstOrCreate(
            ['risk' => 'Premature server power-down before archival checksum verification completes.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Mandate multi-party cryptographic signature sign-off before hardware decommission.',
                'status' => 'Hit',
            ]
        );

        $plan1_3->riskManagements()->firstOrCreate(
            ['risk' => 'Unforeseen external reporting dependency on archived historical tables.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Provide on-demand Athena query gateway into Glacier archival buckets.',
                'status' => 'Hit',
            ]
        );

        $plan1_3->budgets()->firstOrCreate(
            ['description' => 'Glacier Vault Long-Term Storage Pre-Commitment'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_3_1->id,
                'quantity' => '6000',
                'unit' => 'USD',
                'actual' => '5850',
                'status' => 'Hit',
            ]
        );

        $plan1_3->budgets()->firstOrCreate(
            ['description' => 'Certified Hardware Destruction & Recycling Vendor Contract'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t1_3_2->id,
                'quantity' => '8500',
                'unit' => 'USD',
                'actual' => '8500',
                'status' => 'Hit',
            ]
        );

        $t1_3_1->comments()->firstOrCreate(
            ['body' => 'SHA-256 manifest confirmed 100% data integrity for all 15TB cold archives.'],
            ['user_id' => $executor1->id]
        );

        $t1_3_2->comments()->firstOrCreate(
            ['body' => 'NIST SP 800-88 sanitization certificates filed with compliance.'],
            ['user_id' => $marshall->id]
        );

        // =========================================================================
        // EXECUTOR 2 (Sarah Executor) - 3 Plan Records & Components
        // =========================================================================

        // Plan 2.1: Open
        $plan2_1 = PlanRecord::firstOrCreate(
            ['title' => 'Zero-Trust Edge Network & Firewall Deployment'],
            [
                'description' => 'Implement micro-segmentation across edge gateways, deploy mutual TLS for service communication, and configure next-generation firewall policies.',
                'executor_id' => $executor2->id,
                'project_id' => $project2->id,
                'status' => 'Open',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
            ]
        );

        $t2_1_1 = $plan2_1->targetObjectives()->firstOrCreate(
            ['description' => 'Deploy Envoy service proxy with mTLS across 40 edge microservices.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '40',
                'unit' => 'Services',
                'priority' => 'critical',
                'actual' => '40',
                'status' => 'Hit',
            ]
        );

        $t2_1_2 = $plan2_1->targetObjectives()->firstOrCreate(
            ['description' => 'Enforce least-privilege egress filtering rules on all DMZ gateways.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '12',
                'unit' => 'Gateways',
                'priority' => 'high',
                'actual' => null,
                'status' => null,
            ]
        );

        $t2_1_3 = $plan2_1->targetObjectives()->firstOrCreate(
            ['description' => 'Complete automated penetration testing on edge perimeter.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '1',
                'unit' => 'Audit',
                'priority' => 'low',
                'actual' => null,
                'status' => null,
            ]
        );

        $plan2_1->resources()->firstOrCreate(
            ['description' => 'Senior Network Security Engineers for firewall policy migration.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_1_1->id,
                'quantity' => '2',
                'unit' => 'Engineers',
                'actual' => '2',
                'target_date' => now()->addDays(5),
                'date_available' => now()->subDays(2),
                'status' => 'Hit',
            ]
        );

        $plan2_1->resources()->firstOrCreate(
            ['description' => 'Hardware Security Modules (HSM) for automated root CA key rotation.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_1_1->id,
                'quantity' => '4',
                'unit' => 'Units',
                'actual' => null,
                'target_date' => now()->addDays(10),
                'date_available' => now()->addDays(3),
                'status' => null,
            ]
        );

        $plan2_1->riskManagements()->firstOrCreate(
            ['risk' => 'Edge proxy latency spike during peak customer transaction hours.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Implement eBPF kernel-bypass acceleration and tune TCP connection pooling.',
                'status' => null,
            ]
        );

        $r2_1_2 = $plan2_1->riskManagements()->firstOrCreate(
            ['risk' => 'Certificate expiration causing intermittent microservice communication disruption.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Integrate cert-manager with automated 30-day renewal alerts and health checks.',
                'status' => 'Hit',
            ]
        );

        $plan2_1->budgets()->firstOrCreate(
            ['description' => 'Enterprise Firewall Appliance Licenses & Subscriptions'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_1_1->id,
                'quantity' => '18000',
                'unit' => 'USD',
                'actual' => '17500',
                'status' => 'Hit',
            ]
        );

        $plan2_1->budgets()->firstOrCreate(
            ['description' => 'External Penetration Testing Vendor Retainer'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_1_3->id,
                'quantity' => '7500',
                'unit' => 'USD',
                'actual' => null,
                'status' => null,
            ]
        );

        $t2_1_1->comments()->firstOrCreate(
            ['body' => 'All Envoy sidecars injected and mTLS cert validation passing in pre-prod.'],
            ['user_id' => $executor2->id]
        );

        $t2_1_1->comments()->firstOrCreate(
            ['body' => 'Verified zero packet drop during initial load test.'],
            ['user_id' => $marshall->id]
        );

        $r2_1_2->comments()->firstOrCreate(
            ['body' => 'Cert-manager webhooks configured with Vault PKI integration.'],
            ['user_id' => $executor2->id]
        );

        // Plan 2.2: Review
        $plan2_2 = PlanRecord::firstOrCreate(
            ['title' => 'CI/CD Build Pipeline Hardening & SLSA Level 3 Compliance'],
            [
                'description' => 'Secure software supply chain by signing build artifacts with Sigstore/Cosign, eliminating static credentials, and enforcing SLSA Level 3 provenance.',
                'executor_id' => $executor2->id,
                'project_id' => $project1->id,
                'status' => 'Review',
                'start_date' => now()->subDays(20),
                'end_date' => now()->addDays(2),
            ]
        );

        $t2_2_1 = $plan2_2->targetObjectives()->firstOrCreate(
            ['description' => 'Implement cryptographic artifact signing on all production container images.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '25',
                'unit' => 'Pipelines',
                'priority' => 'critical',
                'actual' => '25',
                'status' => 'Hit',
            ]
        );

        $t2_2_2 = $plan2_2->targetObjectives()->firstOrCreate(
            ['description' => 'Migrate runner authentication from static tokens to ephemeral OIDC identity tokens.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '15',
                'unit' => 'Runners',
                'priority' => 'high',
                'actual' => '15',
                'status' => 'Hit',
            ]
        );

        $t2_2_3 = $plan2_2->targetObjectives()->firstOrCreate(
            ['description' => 'Publish automated Software Bill of Materials (SBOM) for all customer-facing releases.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '100',
                'unit' => '%',
                'priority' => 'high',
                'actual' => '85',
                'status' => 'Missed',
            ]
        );

        $plan2_2->resources()->firstOrCreate(
            ['description' => 'Dedicated Build Infrastructure & GitHub Actions Enterprise runners.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_2_2->id,
                'quantity' => '8',
                'unit' => 'Runners',
                'actual' => '8',
                'target_date' => now()->subDays(15),
                'date_available' => now()->subDays(18),
                'status' => 'Hit',
            ]
        );

        $plan2_2->resources()->firstOrCreate(
            ['description' => 'Security Tooling License for automated container vulnerability scanning.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_2_1->id,
                'quantity' => '1',
                'unit' => 'Subscription',
                'actual' => '1',
                'target_date' => now()->subDays(10),
                'date_available' => now()->subDays(12),
                'status' => 'Hit',
            ]
        );

        $plan2_2->riskManagements()->firstOrCreate(
            ['risk' => 'Increased build times due to in-line security vulnerability scanning and SBOM generation.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Cache dependency layers across builds and run deep scans asynchronously in parallel.',
                'status' => 'Hit',
            ]
        );

        $plan2_2->riskManagements()->firstOrCreate(
            ['risk' => 'False positive vulnerability alerts halting urgent production hotfix deployments.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Establish Marshall override protocol with signed security audit exceptions.',
                'status' => 'Hit',
            ]
        );

        $plan2_2->budgets()->firstOrCreate(
            ['description' => 'Cosign Key Management & Hardware Security Module Budget'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_2_1->id,
                'quantity' => '5000',
                'unit' => 'USD',
                'actual' => '4800',
                'status' => 'Hit',
            ]
        );

        $plan2_2->budgets()->firstOrCreate(
            ['description' => 'Dedicated High-Performance CI Runner Compute Credits'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_2_2->id,
                'quantity' => '9200',
                'unit' => 'USD',
                'actual' => '9150',
                'status' => 'Hit',
            ]
        );

        $t2_2_1->comments()->firstOrCreate(
            ['body' => 'Artifact signatures verified by admission controller on staging clusters.'],
            ['user_id' => $executor2->id]
        );

        $t2_2_3->comments()->firstOrCreate(
            ['body' => 'SBOM generation reached 85% coverage; remaining 15% blocked by legacy dependency parser.'],
            ['user_id' => $executor2->id]
        );

        $t2_2_3->comments()->firstOrCreate(
            ['body' => 'Under review by Marshall board. Plan is submitted for sign-off.'],
            ['user_id' => $marshall->id]
        );

        // Plan 2.3: Close
        $plan2_3 = PlanRecord::firstOrCreate(
            ['title' => 'Disaster Recovery Multi-Region Failover Certification'],
            [
                'description' => 'Validate multi-region database replication, DNS Geo-routing failover under load, and verify RTO under 15 minutes and RPO under 1 minute.',
                'executor_id' => $executor2->id,
                'project_id' => $project1->id,
                'status' => 'Close',
                'start_date' => now()->subDays(45),
                'end_date' => now()->subDays(5),
            ]
        );

        $t2_3_1 = $plan2_3->targetObjectives()->firstOrCreate(
            ['description' => 'Execute unannounced failover drill to secondary AWS region without service disruption.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '1',
                'unit' => 'Exercise',
                'priority' => 'critical',
                'actual' => '1',
                'status' => 'Hit',
            ]
        );

        $t2_3_2 = $plan2_3->targetObjectives()->firstOrCreate(
            ['description' => 'Verify Recovery Point Objective (RPO) is strictly under 60 seconds across all stateful stores.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '60',
                'unit' => 'Seconds',
                'priority' => 'critical',
                'actual' => '18',
                'status' => 'Hit',
            ]
        );

        $plan2_3->targetObjectives()->firstOrCreate(
            ['description' => 'Restore cold telemetry storage backups to staging environment within SLA window.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '100',
                'unit' => '%',
                'priority' => 'low',
                'actual' => '100',
                'status' => 'Hit',
            ]
        );

        $plan2_3->resources()->firstOrCreate(
            ['description' => 'Secondary Region Standby Infrastructure compute and storage allocation.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_3_1->id,
                'quantity' => '1',
                'unit' => 'Cluster',
                'actual' => '1',
                'target_date' => now()->subDays(40),
                'date_available' => now()->subDays(42),
                'status' => 'Hit',
            ]
        );

        $plan2_3->resources()->firstOrCreate(
            ['description' => 'Site Reliability Engineering drill coordination strike team.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_3_1->id,
                'quantity' => '4',
                'unit' => 'Engineers',
                'actual' => '4',
                'target_date' => now()->subDays(35),
                'date_available' => now()->subDays(36),
                'status' => 'Hit',
            ]
        );

        $plan2_3->riskManagements()->firstOrCreate(
            ['risk' => 'Split-brain database condition during cross-region network partition drill.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Configure automated quorum witness in third neutral region with fencing tokens.',
                'status' => 'Hit',
            ]
        );

        $plan2_3->riskManagements()->firstOrCreate(
            ['risk' => 'High egress bandwidth billing spikes during database resynchronization phase.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Throttle replication transfer rate outside peak hours and compress WAL streams.',
                'status' => 'Hit',
            ]
        );

        $plan2_3->budgets()->firstOrCreate(
            ['description' => 'Secondary Cloud Region Standby Compute and Egress Reserve'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_3_1->id,
                'quantity' => '22000',
                'unit' => 'USD',
                'actual' => '20850',
                'status' => 'Hit',
            ]
        );

        $plan2_3->budgets()->firstOrCreate(
            ['description' => 'External Disaster Recovery Compliance Auditor Fee'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t2_3_1->id,
                'quantity' => '12000',
                'unit' => 'USD',
                'actual' => '12000',
                'status' => 'Hit',
            ]
        );

        $t2_3_1->comments()->firstOrCreate(
            ['body' => 'Failover drill completed in 8 minutes 42 seconds, well below the 15-minute target.'],
            ['user_id' => $executor2->id]
        );

        $t2_3_2->comments()->firstOrCreate(
            ['body' => 'RPO verified at 18 seconds via transaction logs. Outstanding result.'],
            ['user_id' => $marshall->id]
        );

        // =========================================================================
        // EXECUTOR 3 (Marcus Executor) - 3 Plan Records & Components
        // =========================================================================

        // Plan 3.1: Open
        $plan3_1 = PlanRecord::firstOrCreate(
            ['title' => 'Automated Vulnerability Management & Patch Automation'],
            [
                'description' => 'Integrate continuous container and host scanning, automate security patch pull requests, and enforce zero critical CVE policy in staging environments.',
                'executor_id' => $executor3->id,
                'project_id' => $project2->id,
                'status' => 'Open',
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(27),
            ]
        );

        $t3_1_1 = $plan3_1->targetObjectives()->firstOrCreate(
            ['description' => 'Scan 100% of base operating system images and resolve all critical CVEs.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '50',
                'unit' => 'Images',
                'priority' => 'critical',
                'actual' => '50',
                'status' => 'Hit',
            ]
        );

        $plan3_1->targetObjectives()->firstOrCreate(
            ['description' => 'Automate kernel security updates across Kubernetes worker nodes without downtime.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '24',
                'unit' => 'Nodes',
                'priority' => 'high',
                'actual' => null,
                'status' => null,
            ]
        );

        $t3_1_3 = $plan3_1->targetObjectives()->firstOrCreate(
            ['description' => 'Deploy automated pull request bot for dependency security updates with test verification.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '30',
                'unit' => 'Repositories',
                'priority' => 'low',
                'actual' => null,
                'status' => null,
            ]
        );

        $plan3_1->resources()->firstOrCreate(
            ['description' => 'Dedicated Kubernetes rolling upgrade automation tool and Canary controller.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '1',
                'unit' => 'Controller',
                'actual' => '1',
                'target_date' => now()->addDays(7),
                'date_available' => now()->subDays(1),
                'status' => 'Hit',
            ]
        );

        $plan3_1->resources()->firstOrCreate(
            ['description' => 'Software Vulnerability Database API license with real-time zero-day feeds.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_1_1->id,
                'quantity' => '1',
                'unit' => 'API Key',
                'actual' => '1',
                'target_date' => now()->addDays(4),
                'date_available' => now()->subDays(2),
                'status' => 'Hit',
            ]
        );

        $plan3_1->riskManagements()->firstOrCreate(
            ['risk' => 'Automated patch updates causing breaking changes in upstream dependencies.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Mandate automated end-to-end regression test suite pass before patch merge.',
                'status' => null,
            ]
        );

        $plan3_1->riskManagements()->firstOrCreate(
            ['risk' => 'Node draining causing resource starvation on remaining Kubernetes nodes.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Configure cluster autoscaler buffer capacity of 20% prior to rolling updates.',
                'status' => 'Hit',
            ]
        );

        $plan3_1->budgets()->firstOrCreate(
            ['description' => 'Commercial Vulnerability Intelligence Feed Annual Subscription'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_1_1->id,
                'quantity' => '14000',
                'unit' => 'USD',
                'actual' => '13500',
                'status' => 'Hit',
            ]
        );

        $plan3_1->budgets()->firstOrCreate(
            ['description' => 'Automated Dependency Scanner Enterprise Account'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_1_3->id,
                'quantity' => '6000',
                'unit' => 'USD',
                'actual' => null,
                'status' => null,
            ]
        );

        $t3_1_1->comments()->firstOrCreate(
            ['body' => 'All 50 base OS images scanned. Zero critical vulnerabilities remaining in production base.'],
            ['user_id' => $executor3->id]
        );

        $t3_1_1->comments()->firstOrCreate(
            ['body' => 'Canary controller rollout tested on staging worker pool successfully.'],
            ['user_id' => $executor3->id]
        );

        // Plan 3.2: Review
        $plan3_2 = PlanRecord::firstOrCreate(
            ['title' => 'Real-time Telemetry & Security Information Event Management (SIEM)'],
            [
                'description' => 'Aggregate audit logs from cloud providers, firewalls, and application clusters into a high-throughput vector pipeline with automated anomaly detection alerts.',
                'executor_id' => $executor3->id,
                'project_id' => $project5->id,
                'status' => 'Review',
                'start_date' => now()->subDays(18),
                'end_date' => now()->addDays(5),
            ]
        );

        $t3_2_1 = $plan3_2->targetObjectives()->firstOrCreate(
            ['description' => 'Ingest 500GB daily log volume into distributed OpenSearch cluster.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '500',
                'unit' => 'GB/day',
                'priority' => 'critical',
                'actual' => '520',
                'status' => 'Hit',
            ]
        );

        $t3_2_2 = $plan3_2->targetObjectives()->firstOrCreate(
            ['description' => 'Configure 30 automated behavioral threat detection rules in SIEM dashboard.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '30',
                'unit' => 'Rules',
                'priority' => 'high',
                'actual' => '30',
                'status' => 'Hit',
            ]
        );

        $t3_2_3 = $plan3_2->targetObjectives()->firstOrCreate(
            ['description' => 'Achieve mean time to detect (MTTD) under 3 minutes for simulated unauthorized access.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '3',
                'unit' => 'Minutes',
                'priority' => 'high',
                'actual' => '2.4',
                'status' => 'Hit',
            ]
        );

        $plan3_2->resources()->firstOrCreate(
            ['description' => 'Log ingestion pipelines and Vector collectors deployed on all edge clusters.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_2_1->id,
                'quantity' => '16',
                'unit' => 'Collectors',
                'actual' => '16',
                'target_date' => now()->subDays(12),
                'date_available' => now()->subDays(14),
                'status' => 'Hit',
            ]
        );

        $plan3_2->resources()->firstOrCreate(
            ['description' => 'Security Operations Center (SOC) Lead Analyst for detection rule authoring.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_2_2->id,
                'quantity' => '1',
                'unit' => 'Analyst',
                'actual' => '1',
                'target_date' => now()->subDays(8),
                'date_available' => now()->subDays(10),
                'status' => 'Hit',
            ]
        );

        $r3_2_1 = $plan3_2->riskManagements()->firstOrCreate(
            ['risk' => 'Log ingestion backpressure leading to dropped security audit events during traffic spikes.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Implement durable distributed Kafka buffer topic with 72-hour retention capacity.',
                'status' => 'Hit',
            ]
        );

        $plan3_2->riskManagements()->firstOrCreate(
            ['risk' => 'Excessive alert fatigue caused by un-tuned behavioral detection rules.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Perform 14-day silent alert baselining before promoting detections to live page alerts.',
                'status' => 'Hit',
            ]
        );

        $plan3_2->budgets()->firstOrCreate(
            ['description' => 'OpenSearch Cluster Storage & Compute Capacity Reservation'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_2_1->id,
                'quantity' => '16500',
                'unit' => 'USD',
                'actual' => '15900',
                'status' => 'Hit',
            ]
        );

        $plan3_2->budgets()->firstOrCreate(
            ['description' => 'Managed Kafka Ingestion Streaming Buffer Infrastructure'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_2_1->id,
                'quantity' => '8000',
                'unit' => 'USD',
                'actual' => '7850',
                'status' => 'Hit',
            ]
        );

        $t3_2_1->comments()->firstOrCreate(
            ['body' => 'Log volume stabilized around 520GB/day with zero dropped packets.'],
            ['user_id' => $executor3->id]
        );

        $t3_2_3->comments()->firstOrCreate(
            ['body' => 'Simulated red-team credential replay triggered alerts in 2.4 minutes.'],
            ['user_id' => $marshall->id]
        );

        $r3_2_1->comments()->firstOrCreate(
            ['body' => 'Kafka buffer handled a 3x traffic burst without latency increase.'],
            ['user_id' => $executor3->id]
        );

        // Plan 3.3: Close
        $plan3_3 = PlanRecord::firstOrCreate(
            ['title' => 'Identity & Access Management (IAM) Privilege Auditing'],
            [
                'description' => 'Audit and revoke dormant cloud IAM roles, enforce mandatory hardware MFA keys across all operational personnel, and establish session duration limits.',
                'executor_id' => $executor3->id,
                'project_id' => $project1->id,
                'status' => 'Close',
                'start_date' => now()->subDays(40),
                'end_date' => now()->subDays(2),
            ]
        );

        $t3_3_1 = $plan3_3->targetObjectives()->firstOrCreate(
            ['description' => 'Audit and remove 100% of dormant IAM roles unused for over 90 days.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '85',
                'unit' => 'Roles',
                'priority' => 'high',
                'actual' => '85',
                'status' => 'Hit',
            ]
        );

        $t3_3_2 = $plan3_3->targetObjectives()->firstOrCreate(
            ['description' => 'Distribute and enforce FIDO2 Hardware Security Keys for all engineers.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '45',
                'unit' => 'Keys',
                'priority' => 'critical',
                'actual' => '45',
                'status' => 'Hit',
            ]
        );

        $plan3_3->targetObjectives()->firstOrCreate(
            ['description' => 'Reduce maximum AWS IAM CLI session token duration to 60 minutes.'],
            [
                'user_id' => $marshall->id,
                'quantity' => '60',
                'unit' => 'Minutes',
                'priority' => 'low',
                'actual' => '60',
                'status' => 'Hit',
            ]
        );

        $plan3_3->resources()->firstOrCreate(
            ['description' => 'Hardware security keys (YubiKeys) procurement batch.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_3_2->id,
                'quantity' => '50',
                'unit' => 'Keys',
                'actual' => '50',
                'target_date' => now()->subDays(35),
                'date_available' => now()->subDays(36),
                'status' => 'Hit',
            ]
        );

        $plan3_3->resources()->firstOrCreate(
            ['description' => 'Identity Governance automated scanning script & Marshall review team.'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_3_1->id,
                'quantity' => '1',
                'unit' => 'Review Team',
                'actual' => '1',
                'target_date' => now()->subDays(30),
                'date_available' => now()->subDays(32),
                'status' => 'Hit',
            ]
        );

        $plan3_3->riskManagements()->firstOrCreate(
            ['risk' => 'Engineer lockout from emergency break-glass production access during incident.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 2 - At least 2 or more target objectives are affected',
                'mitigation' => 'Store audited break-glass hardware keys in tamper-evident physical safes with dual-custody.',
                'status' => 'Hit',
            ]
        );

        $plan3_3->riskManagements()->firstOrCreate(
            ['risk' => 'CLI session expiry disrupting long-running automated administrative scripts.'],
            [
                'user_id' => $marshall->id,
                'impact' => 'Lv 1 - Only one target object is affected',
                'mitigation' => 'Migrate long-running tasks to dedicated service accounts with workload identity federation.',
                'status' => 'Hit',
            ]
        );

        $plan3_3->budgets()->firstOrCreate(
            ['description' => 'FIDO2 Hardware Security Key Bulk Purchase'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_3_2->id,
                'quantity' => '4500',
                'unit' => 'USD',
                'actual' => '4250',
                'status' => 'Hit',
            ]
        );

        $plan3_3->budgets()->firstOrCreate(
            ['description' => 'IAM Governance & Compliance External Audit Certification'],
            [
                'user_id' => $marshall->id,
                'target_objective_id' => $t3_3_1->id,
                'quantity' => '11000',
                'unit' => 'USD',
                'actual' => '11000',
                'status' => 'Hit',
            ]
        );

        $t3_3_1->comments()->firstOrCreate(
            ['body' => '85 dormant roles successfully pruned across all 6 cloud accounts.'],
            ['user_id' => $executor3->id]
        );

        $t3_3_2->comments()->firstOrCreate(
            ['body' => '100% of engineering staff registered and verified their hardware keys.'],
            ['user_id' => $executor3->id]
        );

        $t3_3_2->comments()->firstOrCreate(
            ['body' => 'Compliance audit signed and certified for ISO 27001 evidence.'],
            ['user_id' => $marshall->id]
        );
    }
}
