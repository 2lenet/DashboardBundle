<?php

namespace Lle\Tests\Services;

use Lle\DashboardBundle\Dto\ConfiguredStaticWidget;
use Lle\DashboardBundle\Entity\StaticWidgetConfig;
use Lle\DashboardBundle\Repository\StaticWidgetConfigRepository;
use Lle\DashboardBundle\Service\StaticWidgetConfigService;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Lle\Tests\Fixtures\FilterableWidget;
use Lle\Tests\Fixtures\PlainWidget;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class StaticWidgetConfigServiceTest extends TestCase
{
    private const string USER = 'jdoe';
    private const string WIDGET_KEY = 'project_exposure';
    private const string FORM_NAME = StaticWidgetConfigService::FORM_NAME_PREFIX . self::WIDGET_KEY;
    private const string CSRF_TOKEN = 'valid-token';

    /** @var array<string, StaticWidgetConfig> */
    private array $storedConfigs = [];

    public function testWidgetWithoutConfigFormIsReturnedUntouched(): void
    {
        $widget = new PlainWidget();

        $configured = $this->createService()->configure(self::WIDGET_KEY, $widget);

        $this->assertSame($widget, $configured->getWidget());
        $this->assertNull($configured->getForm());
        $this->assertFalse($configured->isCustomized());
    }

    public function testNoFormIsOfferedWithoutAnAuthenticatedUser(): void
    {
        $widget = new FilterableWidget();

        $configured = $this->createService(null)->configure(self::WIDGET_KEY, $widget);

        $this->assertSame($widget, $configured->getWidget());
        $this->assertNull($configured->getForm());
    }

    public function testWidgetWithoutStoredConfigGetsAnEmptyForm(): void
    {
        $widget = new FilterableWidget();

        $configured = $this->createService()->configure(self::WIDGET_KEY, $widget);

        $this->assertNotNull($configured->getForm());
        $this->assertSame(self::FORM_NAME, $configured->getForm()->vars['name']);
        $this->assertFalse($configured->isCustomized());
        $this->assertNull($this->configOf($configured, 'level'));
    }

    public function testStoredConfigIsRehydratedAndMergedOverTheProviderConfig(): void
    {
        $widget = new FilterableWidget();
        $widget->setConfig(['title' => 'Provider title', 'level' => 1]);
        $this->store(self::USER, self::WIDGET_KEY, ['level' => '5', 'ecosystem' => 'npm']);

        $configured = $this->createService()->configure(self::WIDGET_KEY, $widget);

        $configuredWidget = $this->asAbstractWidget($configured);
        $this->assertTrue($configured->isCustomized());
        $this->assertSame(5, $configuredWidget->getConfig('level'));
        $this->assertSame('npm', $configuredWidget->getConfig('ecosystem'));
        $this->assertSame('Provider title', $configuredWidget->getTitle());
        $this->assertSame('5', $configured->getForm()?->children['level']->vars['value']);
    }

    public function testConfiguringNeverMutatesTheSharedWidgetService(): void
    {
        $widget = new FilterableWidget();
        $widget->setConfig(['level' => 1]);
        $this->store(self::USER, self::WIDGET_KEY, ['level' => '5']);

        $configured = $this->createService()->configure(self::WIDGET_KEY, $widget);

        $this->assertNotSame($widget, $configured->getWidget());
        $this->assertSame(1, $widget->getConfig('level'));
    }

    public function testConfigIsScopedToTheCurrentUser(): void
    {
        $this->store('someone_else', self::WIDGET_KEY, ['level' => '5']);

        $configured = $this->createService()->configure(self::WIDGET_KEY, new FilterableWidget());

        $this->assertFalse($configured->isCustomized());
        $this->assertNull($this->configOf($configured, 'level'));
    }

    public function testStoredConfigThatIsNoLongerValidFallsBackToTheDefaults(): void
    {
        $this->store(self::USER, self::WIDGET_KEY, ['ecosystem' => 'docker']);

        $configured = $this->createService()->configure(self::WIDGET_KEY, new FilterableWidget());

        $this->assertTrue($configured->isCustomized());
        $this->assertNull($this->configOf($configured, 'ecosystem'));
    }

    public function testSaveStoresTheSubmittedValuesForTheCurrentUser(): void
    {
        $request = $this->createRequest(['level' => '3', 'ecosystem' => 'composer']);

        $configured = $this->createService()->save(self::WIDGET_KEY, new FilterableWidget(), $request);

        $stored = $this->storedConfigs[self::USER . '|' . self::WIDGET_KEY] ?? null;
        $this->assertNotNull($stored);
        $this->assertSame(['level' => '3', 'ecosystem' => 'composer'], $stored->getConfig());
        $this->assertTrue($configured->isCustomized());
        $this->assertSame(3, $this->configOf($configured, 'level'));
    }

    public function testSaveNeverStoresTheCsrfToken(): void
    {
        $this->createService()->save(self::WIDGET_KEY, new FilterableWidget(), $this->createRequest(['level' => '3']));

        $stored = $this->storedConfigs[self::USER . '|' . self::WIDGET_KEY] ?? null;
        $this->assertNotNull($stored);
        $this->assertArrayNotHasKey('_token', $stored->getConfig());
    }

    public function testSubmissionWithAnInvalidCsrfTokenIsNotStored(): void
    {
        $request = $this->createRequest(['level' => '3', '_token' => 'forged']);

        $configured = $this->createService()->save(self::WIDGET_KEY, new FilterableWidget(), $request);

        $this->assertSame([], $this->storedConfigs);
        $this->assertFalse($configured->getForm()?->vars['valid']);
    }

    public function testSaveUpdatesTheExistingRow(): void
    {
        $existing = $this->store(self::USER, self::WIDGET_KEY, ['level' => '1']);

        $this->createService()->save(self::WIDGET_KEY, new FilterableWidget(), $this->createRequest(['level' => '2']));

        $this->assertCount(1, $this->storedConfigs);
        $this->assertSame($existing, $this->storedConfigs[self::USER . '|' . self::WIDGET_KEY]);
        $this->assertSame(['level' => '2'], $existing->getConfig());
    }

    public function testInvalidSubmissionIsNotStoredAndKeepsTheFormErrors(): void
    {
        $this->store(self::USER, self::WIDGET_KEY, ['level' => '1']);

        $configured = $this->createService()->save(
            self::WIDGET_KEY,
            new FilterableWidget(),
            $this->createRequest(['level' => 'not a number']),
        );

        $this->assertSame(['level' => '1'], $this->storedConfigs[self::USER . '|' . self::WIDGET_KEY]->getConfig());
        $this->assertFalse($configured->getForm()?->vars['valid']);
        $this->assertSame(1, $this->configOf($configured, 'level'));
    }

    public function testSaveRequiresAnAuthenticatedUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->createService(null)->save(self::WIDGET_KEY, new FilterableWidget(), $this->createRequest(['level' => '2']));
    }

    public function testSaveRequiresAConfigurableWidget(): void
    {
        $this->expectException(\LogicException::class);

        $this->createService()->save(self::WIDGET_KEY, new PlainWidget(), $this->createRequest(['level' => '2']));
    }

    public function testResetRemovesTheStoredConfig(): void
    {
        $this->store(self::USER, self::WIDGET_KEY, ['level' => '1']);
        $this->store('someone_else', self::WIDGET_KEY, ['level' => '4']);

        $configured = $this->createService()->reset(self::WIDGET_KEY, new FilterableWidget());

        $this->assertArrayNotHasKey(self::USER . '|' . self::WIDGET_KEY, $this->storedConfigs);
        $this->assertArrayHasKey('someone_else|' . self::WIDGET_KEY, $this->storedConfigs);
        $this->assertFalse($configured->isCustomized());
        $this->assertNull($this->configOf($configured, 'level'));
    }

    private function asAbstractWidget(ConfiguredStaticWidget $configured): AbstractWidget
    {
        $widget = $configured->getWidget();
        $this->assertInstanceOf(AbstractWidget::class, $widget);

        return $widget;
    }

    private function configOf(ConfiguredStaticWidget $configured, string $key): mixed
    {
        return $this->asAbstractWidget($configured)->getConfig($key);
    }

    private function createService(?string $userIdentifier = self::USER): StaticWidgetConfigService
    {
        $formRegistry = new FormRegistry(
            [new CoreExtension(), new HttpFoundationExtension(), new CsrfExtension($this->createCsrfTokenManager())],
            new ResolvedFormTypeFactory(),
        );

        $tokenStorage = new TokenStorage();
        if ($userIdentifier !== null) {
            $user = new InMemoryUser($userIdentifier, null);
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }

        return new StaticWidgetConfigService(
            new FormFactory($formRegistry),
            $formRegistry,
            $this->createRepository(),
            $tokenStorage,
        );
    }

    private function createCsrfTokenManager(): CsrfTokenManagerInterface
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('getToken')->willReturnCallback(
            static fn (string $tokenId): CsrfToken => new CsrfToken($tokenId, self::CSRF_TOKEN),
        );
        $csrfTokenManager->method('isTokenValid')->willReturnCallback(
            static fn (CsrfToken $token): bool => $token->getValue() === self::CSRF_TOKEN,
        );

        return $csrfTokenManager;
    }

    private function createRepository(): StaticWidgetConfigRepository
    {
        $repository = $this->createMock(StaticWidgetConfigRepository::class);
        $repository->method('findOneByUserAndWidget')->willReturnCallback(
            fn (string $user, string $widgetKey): ?StaticWidgetConfig => $this->storedConfigs[$user . '|' . $widgetKey] ?? null,
        );
        $repository->method('save')->willReturnCallback(function (StaticWidgetConfig $config): void {
            $this->storedConfigs[$config->getUserIdentifier() . '|' . $config->getWidgetKey()] = $config;
        });
        $repository->method('remove')->willReturnCallback(function (StaticWidgetConfig $config): void {
            unset($this->storedConfigs[$config->getUserIdentifier() . '|' . $config->getWidgetKey()]);
        });

        return $repository;
    }

    private function store(string $userIdentifier, string $widgetKey, array $config): StaticWidgetConfig
    {
        $stored = new StaticWidgetConfig($userIdentifier, $widgetKey);
        $stored->setConfig($config);
        $this->storedConfigs[$userIdentifier . '|' . $widgetKey] = $stored;

        return $stored;
    }

    private function createRequest(array $values): Request
    {
        return Request::create('/dashboard/static_widget_config/' . self::WIDGET_KEY, 'POST', [
            self::FORM_NAME => $values + ['_token' => self::CSRF_TOKEN],
        ]);
    }
}
